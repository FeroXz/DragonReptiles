import { GeneDef, Zygosity } from './types.js';

export interface PhenotypeOptions {
  includeHet?: boolean;
}

interface NormalizedEntry {
  state: Zygosity;
}

function normalizeEntry(entry: Zygosity | { state: Zygosity } | undefined): NormalizedEntry | null {
  if (!entry) {
    return null;
  }
  if (typeof entry === 'string') {
    return { state: entry };
  }
  return { state: entry.state };
}

function normalizeToken(token: string): string {
  return token.trim();
}

function superLabelForGene(gene: GeneDef): string {
  if (gene.key === 'anaconda') {
    return 'Superconda';
  }
  if (gene.key === 'leatherback') {
    return 'Silkback';
  }
  return `Super ${gene.name}`;
}

function tokenForState(gene: GeneDef, state: Zygosity): string | null {
  switch (gene.type) {
    case 'recessive':
      if (state === 'expressed') {
        return gene.name;
      }
      if (state === 'het') {
        return `het ${gene.name}`;
      }
      return null;
    case 'incomplete_dominant':
      if (state === 'super') {
        return superLabelForGene(gene);
      }
      if (state === 'expressed') {
        return gene.name;
      }
      return null;
    case 'dominant':
      if (state === 'expressed') {
        return gene.name;
      }
      return null;
    case 'polygenic':
      if (state === 'expressed' && gene.visible !== false) {
        return gene.name;
      }
      return null;
    default:
      return null;
  }
}

function priorityForToken(gene: GeneDef, state: Zygosity): number {
  if (state === 'super') {
    return 0;
  }
  if (gene.type === 'incomplete_dominant' || gene.type === 'dominant') {
    return 1;
  }
  if (gene.type === 'recessive' && state === 'expressed') {
    return 2;
  }
  if (gene.type === 'recessive' && state === 'het') {
    return 3;
  }
  if (gene.type === 'polygenic') {
    return 4;
  }
  return 5;
}

export function buildPhenotypeTokens(
  genotype: Record<string, Zygosity | { state: Zygosity }>,
  genes: GeneDef[],
  options: PhenotypeOptions = {}
): string[] {
  const includeHet = options.includeHet ?? true;
  const tokens: Array<{ label: string; priority: number }> = [];

  genes.forEach((gene) => {
    const entry = normalizeEntry(genotype[gene.key]);
    if (!entry) {
      return;
    }
    if (!includeHet && gene.type === 'recessive' && entry.state === 'het') {
      return;
    }
    const label = tokenForState(gene, entry.state);
    if (!label) {
      return;
    }
    tokens.push({ label, priority: priorityForToken(gene, entry.state) });
  });

  const deduped = new Map<string, { label: string; priority: number }>();
  tokens.forEach((entry) => {
    const normalized = normalizeToken(entry.label);
    if (!normalized) {
      return;
    }
    const existing = deduped.get(normalized);
    if (!existing || entry.priority < existing.priority) {
      deduped.set(normalized, entry);
    }
  });

  return Array.from(deduped.values())
    .sort((a, b) => {
      if (a.priority !== b.priority) {
        return a.priority - b.priority;
      }
      return a.label.localeCompare(b.label, 'de');
    })
    .map((entry) => entry.label);
}

export function formatPhenotype(tokens: string[]): string {
  const unique = Array.from(new Set(tokens.map((token) => normalizeToken(token)).filter(Boolean)));
  return unique.join(' ');
}

function genotypeStateLabel(gene: GeneDef, state: Zygosity): string {
  if (gene.type === 'polygenic' && state === 'expressed') {
    return 'line';
  }
  if (state === 'expressed') {
    return gene.name.toLowerCase();
  }
  if (state === 'super') {
    return 'super';
  }
  if (state === 'het') {
    return 'het';
  }
  return 'normal';
}

export function formatGenotypeCompact(
  genotype: Record<string, Zygosity>,
  genes: GeneDef[]
): string {
  const segments: string[] = [];
  genes.forEach((gene) => {
    const state = genotype[gene.key];
    if (!state || state === 'normal') {
      return;
    }
    segments.push(`${gene.name}/${genotypeStateLabel(gene, state)}`);
  });
  return segments.join('; ');
}
