import { useMemo } from 'react';
import clsx from 'clsx';
import { PairingResult, GeneDef } from '@lib/genetics/types.js';

type TraitVariant = 'id-super' | 'id' | 'recessive' | 'dominant' | 'het' | 'het-pct' | 'poly';

interface TraitBadge {
  key: string;
  label: string;
  variant: TraitVariant;
}

interface MorphAlias {
  name: string;
  genes: string[];
}

interface ResultTableProps {
  heading: string;
  notCalculatedText: string;
  emptyText: string;
  remainderTextTemplate: string;
  calculated: boolean;
  results: PairingResult[];
  genes: GeneDef[];
  remainderProbability: number;
  morphAliases: MorphAlias[];
}

interface ViewRow {
  key: string;
  probability: number;
  fraction?: string;
  percent: string;
  traits: TraitBadge[];
  morphName: string;
}

const percentFormatter = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 0 });

const BADGE_PRIORITY: Record<TraitVariant, number> = {
  'id-super': 0,
  id: 1,
  recessive: 2,
  dominant: 3,
  het: 4,
  'het-pct': 5,
  poly: 6
};

function probabilityFraction(probability: number): string | undefined {
  if (probability <= 0) {
    return undefined;
  }
  const tolerance = 1e-5;
  for (let exponent = 1; exponent <= 7; exponent += 1) {
    const denom = 2 ** exponent;
    const fraction = 1 / denom;
    if (Math.abs(probability - fraction) < tolerance) {
      return `1/${denom}`;
    }
  }
  return undefined;
}

function normalizeIdentifier(value: string): string {
  return value.trim().toLowerCase();
}

function geneMatchesAlias(identifier: string, gene: GeneDef): boolean {
  const normalized = normalizeIdentifier(identifier);
  if (normalizeIdentifier(gene.key) === normalized) {
    return true;
  }
  if (gene.aliases?.some((alias) => normalizeIdentifier(alias) === normalized)) {
    return true;
  }
  if (gene.searchAliases?.some((alias) => normalizeIdentifier(alias) === normalized)) {
    return true;
  }
  return false;
}

function resolveAliasGene(identifier: string, genes: GeneDef[]): GeneDef | undefined {
  return genes.find((gene) => geneMatchesAlias(identifier, gene));
}

function stateIsActive(gene: GeneDef, state: string): boolean {
  if (gene.type === 'recessive') {
    return state === 'expressed';
  }
  if (gene.type === 'incomplete_dominant') {
    return state === 'expressed' || state === 'super';
  }
  if (gene.type === 'dominant') {
    return state === 'expressed';
  }
  if (gene.type === 'polygenic') {
    return state === 'expressed';
  }
  return false;
}

function buildBadges(result: PairingResult, genes: GeneDef[]): TraitBadge[] {
  const badges: TraitBadge[] = [];
  const seen = new Map<string, TraitBadge>();

  genes.forEach((gene) => {
    const state = result.genotype[gene.key];
    if (!state || state === 'normal') {
      return;
    }
    let badge: TraitBadge | null = null;
    if (gene.type === 'incomplete_dominant') {
      if (state === 'super') {
        badge = { key: `${gene.key}:super`, label: gene.superLabel || `Super ${gene.name}`, variant: 'id-super' };
      } else if (state === 'expressed') {
        badge = { key: `${gene.key}:id`, label: gene.name, variant: 'id' };
      }
    } else if (gene.type === 'recessive') {
      if (state === 'expressed') {
        badge = { key: `${gene.key}:rec`, label: gene.name, variant: 'recessive' };
      } else if (state === 'het') {
        badge = { key: `${gene.key}:het`, label: `het ${gene.name}`, variant: 'het' };
      }
    } else if (gene.type === 'dominant') {
      if (state === 'expressed') {
        badge = { key: `${gene.key}:dom`, label: gene.name, variant: 'dominant' };
      }
    } else if (gene.type === 'polygenic') {
      if (state === 'expressed') {
        badge = { key: `${gene.key}:poly`, label: `Linie ${gene.name}`, variant: 'poly' };
      }
    }
    if (badge) {
      const existing = seen.get(badge.label.toLowerCase());
      if (!existing || BADGE_PRIORITY[badge.variant] < BADGE_PRIORITY[existing.variant]) {
        seen.set(badge.label.toLowerCase(), badge);
      }
    }
  });

  badges.push(...seen.values());

  badges.sort((a, b) => {
    const orderDiff = BADGE_PRIORITY[a.variant] - BADGE_PRIORITY[b.variant];
    if (orderDiff !== 0) {
      return orderDiff;
    }
    return a.label.localeCompare(b.label, 'de');
  });

  return badges;
}

function buildMorphName(
  result: PairingResult,
  badges: TraitBadge[],
  genes: GeneDef[],
  aliases: MorphAlias[]
): string {
  if (!badges.length) {
    return '';
  }

  const activeKeys = new Set<string>();
  genes.forEach((gene) => {
    const state = result.genotype[gene.key];
    if (stateIsActive(gene, state)) {
      activeKeys.add(gene.key);
    }
  });

  const matchedAliases: string[] = [];
  const usedKeys = new Set<string>();

  aliases.forEach((entry) => {
    const resolvedKeys = entry.genes
      .map((identifier) => resolveAliasGene(identifier, genes))
      .filter((gene): gene is GeneDef => Boolean(gene))
      .map((gene) => gene.key);
    if (resolvedKeys.length !== entry.genes.length) {
      return;
    }
    if (resolvedKeys.every((key) => activeKeys.has(key))) {
      matchedAliases.push(entry.name);
      resolvedKeys.forEach((key) => usedKeys.add(key));
    }
  });

  const leftoverBadges = badges.filter((badge) => {
    if (badge.variant === 'het' || badge.variant === 'het-pct') {
      return false;
    }
    const [geneKey] = badge.key.split(':');
    if (!geneKey) {
      return true;
    }
    return !usedKeys.has(geneKey);
  });

  const tokens = [...matchedAliases, ...leftoverBadges.map((badge) => badge.label)];
  const seen = new Set<string>();
  const uniqueTokens = tokens.filter((token) => {
    const normalized = token.toLowerCase();
    if (seen.has(normalized)) {
      return false;
    }
    seen.add(normalized);
    return true;
  });

  return uniqueTokens.join(' ');
}

function formatTemplate(template: string, params: Record<string, string>): string {
  return template.replace(/\{(\w+)}/g, (_, key) => params[key] ?? '');
}

export function ResultTable({
  heading,
  notCalculatedText,
  emptyText,
  remainderTextTemplate,
  calculated,
  results,
  genes,
  remainderProbability,
  morphAliases
}: ResultTableProps) {
  const rows = useMemo<ViewRow[]>(() => {
    return results.map((result, index) => {
      const probability = result.probability;
      const fraction = probabilityFraction(probability);
      const percent = percentFormatter.format(probability * 100);
      const badges = buildBadges(result, genes);
      const morphName = buildMorphName(result, badges, genes, morphAliases);
      return {
        key: `${index}:${probability.toFixed(5)}`,
        probability,
        fraction,
        percent,
        traits: badges,
        morphName
      };
    });
  }, [results, genes, morphAliases]);
  const remainderPercent = remainderProbability > 0 ? percentFormatter.format(remainderProbability * 100) : null;

  return (
    <section className="mm-results">
      <header className="mm-results-header">
        <h2>{heading}</h2>
      </header>
      {!calculated ? (
        <p className="mm-results-hint">{notCalculatedText}</p>
      ) : rows.length === 0 ? (
        <p className="mm-results-hint">{emptyText}</p>
      ) : (
        <div className="mm-results-table">
          <table className="tbl">
            <thead>
              <tr>
                <th className="prob">Prob</th>
                <th>Traits</th>
                <th>Morph-Name</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.key}>
                  <td className="prob">
                    <span>
                      {row.fraction ? `${row.fraction} (${row.percent}%)` : `${row.percent}%`}
                    </span>
                  </td>
                  <td>
                    <div className="mm-badges">
                      {row.traits.map((trait) => (
                        <span key={trait.key} className={clsx('mm-badge', badgeClassName(trait.variant))}>
                          {trait.label}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td>
                    <span className="mm-morph-name">{row.morphName || '–'}</span>
                  </td>
                </tr>
              ))}
              {remainderPercent ? (
                <tr className="mm-results-remainder">
                  <td className="prob">—</td>
                  <td colSpan={2}>{formatTemplate(remainderTextTemplate, { percent: remainderPercent })}</td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}

function badgeClassName(variant: TraitVariant): string {
  switch (variant) {
    case 'id-super':
      return 'badge-id-super';
    case 'id':
      return 'badge-id';
    case 'recessive':
      return 'badge-rec';
    case 'dominant':
      return 'badge-dom';
    case 'het':
      return 'badge-het';
    case 'het-pct':
      return 'badge-het-pct';
    case 'poly':
      return 'badge-poly';
    default:
      return '';
  }
}

export default ResultTable;
