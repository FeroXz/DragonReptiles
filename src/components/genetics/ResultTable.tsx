import { useMemo } from 'react';
import type { GeneDef, PairingResult, SpeciesKey, Zygosity } from '@lib/genetics/types.js';

interface MorphAliasEntry {
  name: string;
  genes: string[];
  states?: Record<string, Zygosity>;
}

interface ResultTableProps {
  results: PairingResult[];
  genes: GeneDef[];
  species: SpeciesKey;
  aliases: Record<string, MorphAliasEntry[]>;
}

interface NormalizedState {
  state: Zygosity;
  posHet?: number;
}

interface BadgeEntry {
  key: string;
  label: string;
  className: string;
  order: number;
  geneKey: string;
  baseLabel: string;
  contributesToAlias: boolean;
}

const BADGE_CLASS = {
  id: 'badge-id',
  idSuper: 'badge-id-super',
  rec: 'badge-rec',
  dom: 'badge-dom',
  het: 'badge-het',
  hetPct: 'badge-het-pct',
  poly: 'badge-poly'
} as const;

function normalizeGeneState(entry: PairingResult['genotype'][string]): NormalizedState {
  if (!entry) {
    return { state: 'normal' };
  }
  if (typeof entry === 'string') {
    return { state: entry };
  }
  return { state: entry.state, posHet: entry.posHet };
}

function superLabelForGene(gene: GeneDef): string {
  return gene.superLabel ?? `Super ${gene.name}`;
}

function normalizePercent(value: number | undefined): number | undefined {
  if (value === undefined) {
    return undefined;
  }
  const canonical = [33, 50, 66];
  const rounded = Math.round(value);
  for (const target of canonical) {
    if (Math.abs(rounded - target) <= 3) {
      return target;
    }
  }
  return rounded;
}

function fractionForProbability(probability: number): string | null {
  const epsilon = 1e-6;
  for (let exponent = 0; exponent <= 6; exponent += 1) {
    const denominator = 2 ** exponent;
    const numerator = Math.round(probability * denominator);
    if (numerator === 0) {
      continue;
    }
    const diff = Math.abs(probability - numerator / denominator);
    if (diff < epsilon) {
      return `${numerator}/${denominator}`;
    }
  }
  return null;
}

function resolveAliasKeys(
  entries: MorphAliasEntry[],
  genes: GeneDef[]
): Array<{ name: string; keys: string[]; states?: Record<string, Zygosity> }> {
  const geneMap = new Map<string, GeneDef>();
  genes.forEach((gene) => geneMap.set(gene.key, gene));

  const aliasList: Array<{ name: string; keys: string[]; states?: Record<string, Zygosity> }> = [];

  const resolveToken = (token: string): string | null => {
    if (geneMap.has(token)) {
      return token;
    }
    const match = genes.find((gene) => {
      const aliases = [...(gene.aliases ?? []), ...(gene.searchAliases ?? [])];
      return aliases.some((entry) => entry.toLowerCase() === token.toLowerCase());
    });
    return match ? match.key : null;
  };

  entries.forEach((alias) => {
    const keys: string[] = [];
    alias.genes.forEach((token) => {
      const key = resolveToken(token);
      if (key) {
        keys.push(key);
      }
    });
    if (keys.length === alias.genes.length) {
      let states: Record<string, Zygosity> | undefined;
      if (alias.states) {
        const mappedStates: Record<string, Zygosity> = {};
        const entries = Object.entries(alias.states);
        for (const [token, state] of entries) {
          const key = resolveToken(token);
          if (!key || !keys.includes(key)) {
            return;
          }
          mappedStates[key] = state;
        }
        states = mappedStates;
      }
      aliasList.push(states ? { name: alias.name, keys, states } : { name: alias.name, keys });
    }
  });

  return aliasList;
}

function buildBadges(result: PairingResult, genes: GeneDef[]): { badges: BadgeEntry[]; aliasStates: Map<string, Zygosity> } {
  const badges: BadgeEntry[] = [];
  const aliasEligible = new Map<string, Zygosity>();

  genes.forEach((gene) => {
    const entry = normalizeGeneState(result.genotype[gene.key]);
    const { state, posHet } = entry;
    if (state === 'normal' && posHet === undefined) {
      return;
    }

    if (gene.type === 'recessive') {
      if (state === 'expressed') {
        badges.push({
          key: `${gene.key}-rec`,
          label: gene.name,
          className: BADGE_CLASS.rec,
          order: 2,
          geneKey: gene.key,
          baseLabel: gene.name,
          contributesToAlias: true
        });
        aliasEligible.set(gene.key, 'expressed');
      } else if (state === 'het') {
        const percent = normalizePercent(posHet);
        if (percent !== undefined) {
          badges.push({
            key: `${gene.key}-hetpct`,
            label: `${percent}% Het ${gene.name}`,
            className: BADGE_CLASS.hetPct,
            order: 5,
            geneKey: gene.key,
            baseLabel: `Het ${gene.name}`,
            contributesToAlias: false
          });
        } else {
        badges.push({
          key: `${gene.key}-het`,
          label: `Het ${gene.name}`,
          className: BADGE_CLASS.het,
          order: 4,
          geneKey: gene.key,
          baseLabel: `Het ${gene.name}`,
          contributesToAlias: false
        });
        }
      } else if (state === 'normal' && posHet !== undefined) {
        const percent = normalizePercent(posHet);
        if (percent !== undefined) {
          badges.push({
            key: `${gene.key}-poshet`,
            label: `${percent}% Het ${gene.name}`,
            className: BADGE_CLASS.hetPct,
            order: 5,
            geneKey: gene.key,
            baseLabel: `Het ${gene.name}`,
            contributesToAlias: false
          });
        }
      }
      return;
    }

    if (gene.type === 'incomplete_dominant') {
      if (state === 'super') {
        const label = superLabelForGene(gene);
        badges.push({
          key: `${gene.key}-super`,
          label,
          className: BADGE_CLASS.idSuper,
          order: 0,
          geneKey: gene.key,
          baseLabel: label,
          contributesToAlias: true
        });
        aliasEligible.set(gene.key, 'super');
        return;
      }
      if (state === 'expressed') {
        badges.push({
          key: `${gene.key}-id`,
          label: gene.name,
          className: BADGE_CLASS.id,
          order: 1,
          geneKey: gene.key,
          baseLabel: gene.name,
          contributesToAlias: true
        });
        aliasEligible.set(gene.key, 'expressed');
        return;
      }
      return;
    }

    if (gene.type === 'dominant') {
      if (state === 'expressed') {
        badges.push({
          key: `${gene.key}-dom`,
          label: gene.name,
          className: BADGE_CLASS.dom,
          order: 3,
          geneKey: gene.key,
          baseLabel: gene.name,
          contributesToAlias: true
        });
        aliasEligible.set(gene.key, 'expressed');
      }
      return;
    }

    if (gene.type === 'polygenic') {
      if (state === 'expressed') {
        const label = gene.name.startsWith('Linie ')
          ? gene.name
          : `${gene.name}`;
        badges.push({
          key: `${gene.key}-poly`,
          label,
          className: BADGE_CLASS.poly,
          order: 6,
          geneKey: gene.key,
          baseLabel: label,
          contributesToAlias: false
        });
      }
    }
  });

  const sorted = badges.sort((a, b) => {
    if (a.order !== b.order) {
      return a.order - b.order;
    }
    return a.label.localeCompare(b.label, 'de');
  });

  return { badges: sorted, aliasStates: aliasEligible };
}

function stripPercentPrefix(label: string): string {
  return label.replace(/^\d+%\s+/, '').replace(/^Linie\s+/i, '').trim();
}

function buildMorphName(
  badges: BadgeEntry[],
  aliasStates: Map<string, Zygosity>,
  aliases: Array<{ name: string; keys: string[]; states?: Record<string, Zygosity> }>
): string {
  const remaining = new Map(aliasStates);
  const tokens: string[] = [];

  aliases.forEach((alias) => {
    const matches = alias.keys.every((key) => {
      if (!remaining.has(key)) {
        return false;
      }
      if (!alias.states) {
        return true;
      }
      const required = alias.states[key];
      if (!required) {
        return true;
      }
      return remaining.get(key) === required;
    });

    if (matches) {
      tokens.push(alias.name);
      alias.keys.forEach((key) => remaining.delete(key));
    }
  });

  badges.forEach((badge) => {
    if (badge.contributesToAlias && remaining.has(badge.geneKey)) {
      tokens.push(stripPercentPrefix(badge.baseLabel));
      remaining.delete(badge.geneKey);
    } else if (!badge.contributesToAlias) {
      tokens.push(stripPercentPrefix(badge.baseLabel));
    }
  });

  const unique = Array.from(new Set(tokens.filter(Boolean)));
  return unique.join(' ');
}

export function ResultTable({ results, genes, species, aliases }: ResultTableProps) {
  const aliasDefinitions = useMemo(
    () => resolveAliasKeys(aliases[species] ?? [], genes),
    [aliases, species, genes]
  );

  if (!results.length) {
    return (
      <div className="results-table-wrapper nui-card">
        <div className="results-empty">Starte eine Berechnung, um Ergebnisse zu sehen.</div>
      </div>
    );
  }

  return (
    <div className="results-table-wrapper nui-card">
      <div className="result-list" role="list">
        {results.map((result, index) => {
          const { badges, aliasStates } = buildBadges(result, genes);
          const morphName = buildMorphName(badges, aliasStates, aliasDefinitions);
          const percentValue = Math.max(Math.min(result.probability * 100, 100), 0);
          const fraction = fractionForProbability(result.probability);
          const percentLabel = `${Math.round(result.probability * 100)}%`;
          return (
            <article key={`${index}-${result.probability}`} className="result-card" role="listitem">
              <div className="result-card__header">
                <div className="result-card__prob" aria-label={`Wahrscheinlichkeit ${percentLabel}`}>
                  <span className="result-card__fraction">{fraction ?? percentLabel}</span>
                  <span className="result-card__percent">{fraction ? percentLabel : 'Erwartet'}</span>
                </div>
                <div className="result-card__meta">
                  <span className="result-card__label">Morph Name</span>
                  <span className="result-card__name">{morphName || '—'}</span>
                </div>
              </div>
              <div className="result-card__progress" aria-hidden="true">
                <div className="result-card__progress-fill" style={{ width: `${Math.max(percentValue, 2)}%` }} />
              </div>
              <div className="result-card__traits">
                <span className="result-card__label">Traits</span>
                <div className="badge-list">
                  {badges.map((badge) => (
                    <span key={badge.key} className={`mm-badge ${badge.className}`}>
                      {badge.label}
                    </span>
                  ))}
                  {badges.length === 0 && <span className="result-card__placeholder">Normal</span>}
                </div>
              </div>
            </article>
          );
        })}
      </div>
    </div>
  );
}
