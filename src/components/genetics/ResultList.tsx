import { useMemo } from 'react';
import clsx from 'clsx';
import { buildPhenotypeTokens, formatGenotypeCompact, formatPhenotype } from '@lib/genetics/format.js';
import { GeneDef, PairingResult } from '@lib/genetics/types.js';

interface FilterState {
  superOnly: boolean;
  highProbability: boolean;
  showHet: boolean;
}

interface Messages {
  filterSuper: string;
  filterHighProbability: string;
  filterShowHet: string;
  filterLabel: string;
  empty: string;
  remainder: string;
  normalForm: string;
}

interface ResultListProps {
  results: PairingResult[];
  genes: GeneDef[];
  filters: FilterState;
  onFiltersChange: (next: FilterState) => void;
  remainderProbability: number;
  messages: Messages;
}

function hasSuperForm(result: PairingResult): boolean {
  return Object.values(result.genotype).some((state) => state === 'super');
}

function formatProbability(value: number): string {
  return `${Math.round(value * 1000) / 10} %`;
}

function formatRemainder(template: string, probability: number): string {
  const percent = Math.round(probability * 1000) / 10;
  return template.replace('{percent}', `${percent}`);
}

export function ResultList({
  results,
  genes,
  filters,
  onFiltersChange,
  remainderProbability,
  messages
}: ResultListProps) {
  const filtered = useMemo(() => {
    return results.filter((result) => {
      if (filters.superOnly && !hasSuperForm(result)) {
        return false;
      }
      if (filters.highProbability && result.probability < 0.25) {
        return false;
      }
      return true;
    });
  }, [filters, results]);

  const handleToggle = (key: keyof FilterState) => {
    onFiltersChange({ ...filters, [key]: !filters[key] });
  };

  return (
    <section className="result-list">
      <header className="result-list__header">
        <div className="result-list__filters" role="group" aria-label={messages.filterLabel}>
          <button
            type="button"
            className={clsx('chip-button', { 'is-active': filters.superOnly })}
            onClick={() => handleToggle('superOnly')}
          >
            {messages.filterSuper}
          </button>
          <button
            type="button"
            className={clsx('chip-button', { 'is-active': filters.highProbability })}
            onClick={() => handleToggle('highProbability')}
          >
            {messages.filterHighProbability}
          </button>
          <button
            type="button"
            className={clsx('chip-button', { 'is-active': filters.showHet })}
            onClick={() => handleToggle('showHet')}
          >
            {messages.filterShowHet}
          </button>
        </div>
      </header>
      <div className="result-list__content">
        {!filtered.length && <p className="result-list__empty">{messages.empty}</p>}
        {filtered.map((result) => {
          const displayTokens = filters.showHet
            ? result.phenotypeTokens
            : buildPhenotypeTokens(result.genotype, genes, { includeHet: false });
          const primaryLabel = displayTokens.length ? formatPhenotype(displayTokens) : messages.normalForm;
          const secondaryLabel = formatGenotypeCompact(result.genotype, genes);
          const percent = result.probability * 100;
          const genotypeKey = Object.entries(result.genotype)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([key, state]) => `${key}:${state}`)
            .join('|');

          return (
            <article className="result-card" key={`${genotypeKey}-${percent.toFixed(4)}`}>
              <div className="result-card__labels">
                <h4 className="result-card__title">{primaryLabel}</h4>
                {secondaryLabel && <p className="result-card__subtitle">{secondaryLabel}</p>}
              </div>
              <div className="result-card__meta">
                <span className="result-card__probability">{formatProbability(result.probability)}</span>
                <div className="result-card__bar">
                  <div className="result-card__bar-fill" style={{ width: `${percent}%` }} />
                </div>
              </div>
            </article>
          );
        })}
        {remainderProbability > 0 && (
          <div className="result-list__remainder">{formatRemainder(messages.remainder, remainderProbability)}</div>
        )}
      </div>
    </section>
  );
}
