import { useMemo } from 'react';
import clsx from 'clsx';
import { GeneDef, PairingResult } from '@lib/genetics/types.js';
import { buildPhenotypeTokens, formatGenotypeCompact, formatPhenotype } from '@lib/genetics/format.js';

export interface ResultFilters {
  superOnly: boolean;
  topProbability: boolean;
  showHet: boolean;
}

export interface ResultListTranslations {
  heading: string;
  filterSuper: string;
  filterHighProbability: string;
  filterShowHet: string;
  notCalculated: string;
  empty: string;
  remainder: string;
  normalForm: string;
}

interface ResultListProps {
  results: PairingResult[];
  genes: GeneDef[];
  filters: ResultFilters;
  onToggleFilter: (filter: keyof ResultFilters) => void;
  remainderProbability: number;
  calculated: boolean;
  translations: ResultListTranslations;
}

function formatTemplate(template: string, params: Record<string, string>): string {
  return template.replace(/\{(\w+)}/g, (_, key) => params[key] ?? '');
}

function hasSuperForm(result: PairingResult, geneMap: Map<string, GeneDef>): boolean {
  return Object.entries(result.genotype).some(([key, state]) => {
    const gene = geneMap.get(key);
    if (!gene) {
      return false;
    }
    return gene.type === 'incomplete_dominant' && state === 'super';
  });
}

const percentFormatter = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

export function ResultList({
  results,
  genes,
  filters,
  onToggleFilter,
  remainderProbability,
  calculated,
  translations
}: ResultListProps) {
  const geneMap = useMemo(() => new Map(genes.map((gene) => [gene.key, gene])), [genes]);

  const prepared = useMemo(() => {
    return results
      .filter((result) => {
        if (filters.superOnly && !hasSuperForm(result, geneMap)) {
          return false;
        }
        if (filters.topProbability && result.probability < 0.25) {
          return false;
        }
        return true;
      })
      .map((result) => {
        const tokens = buildPhenotypeTokens(result.genotype, genes, { includeHet: filters.showHet });
        const phenotype = formatPhenotype(tokens);
        const genotypeLabel = formatGenotypeCompact(result.genotype, genes);
        return { ...result, tokens, phenotype, genotypeLabel };
      });
  }, [results, filters, genes, geneMap]);

  const remainderText = remainderProbability > 0 ? percentFormatter.format(remainderProbability * 100) : null;

  return (
    <section className="result-list">
      <header className="result-list__header">
        <h2>{translations.heading}</h2>
        <div className="result-list__filters" role="group" aria-label="Filter">
          <button
            type="button"
            className={clsx('filter-chip', { 'is-active': filters.superOnly })}
            onClick={() => onToggleFilter('superOnly')}
          >
            {translations.filterSuper}
          </button>
          <button
            type="button"
            className={clsx('filter-chip', { 'is-active': filters.topProbability })}
            onClick={() => onToggleFilter('topProbability')}
          >
            {translations.filterHighProbability}
          </button>
          <button
            type="button"
            className={clsx('filter-chip', { 'is-active': filters.showHet })}
            onClick={() => onToggleFilter('showHet')}
          >
            {translations.filterShowHet}
          </button>
        </div>
      </header>
      {!calculated ? (
        <p className="result-list__hint">{translations.notCalculated}</p>
      ) : prepared.length === 0 ? (
        <p className="result-list__hint">{translations.empty}</p>
      ) : (
        <div className="result-list__items">
          {prepared.map((result) => {
            const percentage = result.probability * 100;
            const formattedPercent = percentFormatter.format(percentage);
            const primaryLabel = result.phenotype || translations.normalForm;
            return (
              <article className="result-card" key={result.phenotypeTokens.join('|') + percentage.toFixed(5)}>
                <div className="result-card__bar" aria-hidden="true">
                  <div
                    className="result-card__bar-fill"
                    style={{ width: `${Math.max(4, Math.min(100, percentage))}%` }}
                  />
                  <span className="result-card__bar-value">{formattedPercent}%</span>
                </div>
                <div className="result-card__body">
                  <h3 className="result-card__title">{primaryLabel}</h3>
                  <span className="result-card__genotype">{result.genotypeLabel}</span>
                </div>
              </article>
            );
          })}
          {remainderText && (
            <div className="result-card result-card--remainder">
              <span>{formatTemplate(translations.remainder, { percent: remainderText })}</span>
            </div>
          )}
        </div>
      )}
    </section>
  );
}
