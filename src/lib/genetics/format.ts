import { GeneDef, Zygosity } from './types.js';

const SUPER_NAME_OVERRIDES: Record<string, string> = {
  anaconda: 'Superconda',
  leatherback: 'Silkback'
};

function normalizeToken(token: string): string {
  return token.trim();
}

function getSuperName(gene: GeneDef): string {
  return SUPER_NAME_OVERRIDES[gene.key] ?? `Super ${gene.name}`;
}

function describeGeneState(gene: GeneDef, state: Zygosity, options: { includeHet?: boolean } = {}): { token: string; priority: number } | null {
  const includeHet = options.includeHet ?? true;
  if (gene.type === 'polygenic') {
    if (state === 'expressed') {
      return { token: gene.name, priority: 2 };
    }
    return null;
  }

  if (gene.type === 'recessive') {
    if (state === 'expressed') {
      return { token: gene.name, priority: 2 };
    }
    if (state === 'het' && includeHet) {
      return { token: `het ${gene.name}`.trim(), priority: 3 };
    }
    return null;
  }

  if (gene.type === 'incomplete_dominant') {
    if (state === 'super') {
      return { token: getSuperName(gene), priority: 0 };
    }
    if (state === 'expressed') {
      return { token: gene.name, priority: 1 };
    }
    return null;
  }

  if (gene.type === 'dominant') {
    if (state === 'expressed') {
      return { token: gene.name, priority: 1 };
    }
    return null;
  }

  return null;
}

export interface PhenotypeOptions {
  includeHet?: boolean;
}

export function buildPhenotypeTokens(
  genotype: Record<string, Zygosity>,
  genes: GeneDef[],
  options: PhenotypeOptions = {}
): string[] {
  const tokens = genes
    .map((gene) => {
      const state = genotype[gene.key];
      if (!state || state === 'normal') {
        return null;
      }
      return describeGeneState(gene, state, options);
    })
    .filter((entry): entry is { token: string; priority: number } => Boolean(entry));

  const deduped = new Map<string, { token: string; priority: number }>();
  for (const entry of tokens) {
    const normalized = normalizeToken(entry.token);
    if (!normalized) {
      continue;
    }
    if (!deduped.has(normalized)) {
      deduped.set(normalized, entry);
    } else {
      const existing = deduped.get(normalized)!;
      if (entry.priority < existing.priority) {
        deduped.set(normalized, entry);
      }
    }
  }

  return Array.from(deduped.values())
    .sort((a, b) => {
      if (a.priority !== b.priority) {
        return a.priority - b.priority;
      }
      return a.token.localeCompare(b.token, 'de');
    })
    .map((entry) => entry.token);
}

export function formatPhenotype(tokens: string[]): string {
  const unique = Array.from(new Set(tokens.map((token) => normalizeToken(token)).filter(Boolean)));
  return unique.join(' ');
}

export function formatGenotypeCompact(genotype: Record<string, Zygosity>, genes: GeneDef[]): string {
  const segments: string[] = [];
  genes.forEach((gene) => {
    const state = genotype[gene.key] ?? 'normal';
    let label = 'normal';
    switch (gene.type) {
      case 'recessive':
        if (state === 'expressed') {
          label = gene.name.toLowerCase();
        } else if (state === 'het') {
          label = 'het';
        }
        break;
      case 'incomplete_dominant':
        if (state === 'super') {
          label = 'super';
        } else if (state === 'expressed') {
          label = 'expressed';
        }
        break;
      case 'dominant':
        if (state === 'expressed') {
          label = 'expressed';
        }
        break;
      case 'polygenic':
        if (state === 'expressed') {
          label = 'line';
        }
        break;
    }
    segments.push(`${gene.name}/${label}`);
  });
  return segments.join('; ');
}
