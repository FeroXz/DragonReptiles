import { buildOptions, type Option } from './options.js';
import { GeneDef, Zygosity } from './types.js';

function normalizeToken(token: string): string {
  return token.trim();
}

function valueToZygosity(entry: Zygosity | { state: Zygosity } | undefined): Zygosity | null {
  if (!entry) {
    return null;
  }
  if (typeof entry === 'string') {
    return entry;
  }
  return entry.state;
}

function mapStateToOptionState(gene: GeneDef, state: Zygosity): Option['state'] | null {
  if (state === 'normal') {
    return null;
  }
  if (gene.type === 'recessive') {
    if (state === 'het' || state === 'expressed') {
      return state;
    }
    return null;
  }
  if (gene.type === 'incomplete_dominant') {
    if (state === 'super' || state === 'expressed') {
      return state;
    }
    return null;
  }
  if (gene.type === 'dominant') {
    if (state === 'expressed') {
      return 'present';
    }
    return null;
  }
  if (gene.type === 'polygenic') {
    if (state === 'expressed') {
      return 'poly';
    }
    return null;
  }
  return null;
}

function buildOptionIndex(genes: GeneDef[]): Map<string, Option> {
  const index = new Map<string, Option>();
  for (const option of buildOptions(genes)) {
    index.set(`${option.geneKey}:${option.state}`, option);
  }
  return index;
}

function getPriority(option: Option): number {
  if (option.state === 'super') {
    return 0;
  }
  if (option.group === 'inkomplett dominant') {
    return 1;
  }
  if (option.group === 'rezessiv' && option.state === 'expressed') {
    return 2;
  }
  if (option.state === 'het') {
    return 3;
  }
  if (option.group === 'dominant') {
    return 4;
  }
  if (option.group === 'polygen') {
    return 5;
  }
  return 6;
}

export interface PhenotypeOptions {
  includeHet?: boolean;
}

export function buildPhenotypeTokens(
  genotype: Record<string, Zygosity>,
  genes: GeneDef[],
  options: PhenotypeOptions = {}
): string[] {
  const includeHet = options.includeHet ?? true;
  const optionIndex = buildOptionIndex(genes);
  const tokens: Array<{ label: string; priority: number }> = [];

  for (const gene of genes) {
    const state = valueToZygosity(genotype[gene.key]);
    if (!state || state === 'normal') {
      continue;
    }
    const optionState = mapStateToOptionState(gene, state);
    if (!optionState) {
      continue;
    }
    if (!includeHet && optionState === 'het') {
      continue;
    }
    const option = optionIndex.get(`${gene.key}:${optionState}`);
    if (!option) {
      continue;
    }
    tokens.push({ label: option.label, priority: getPriority(option) });
  }

  const deduped = new Map<string, { label: string; priority: number }>();
  for (const entry of tokens) {
    const normalized = normalizeToken(entry.label);
    if (!normalized) {
      continue;
    }
    const existing = deduped.get(normalized);
    if (!existing || entry.priority < existing.priority) {
      deduped.set(normalized, entry);
    }
  }

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

export function formatGenotypeCompact(genotype: Record<string, Zygosity>, genes: GeneDef[]): string {
  const optionIndex = buildOptionIndex(genes);
  const segments: string[] = [];

  genes.forEach((gene) => {
    const state = valueToZygosity(genotype[gene.key]) ?? 'normal';
    const optionState = mapStateToOptionState(gene, state);
    if (!optionState) {
      segments.push(`${gene.name}/normal`);
      return;
    }
    const option = optionIndex.get(`${gene.key}:${optionState}`);
    if (!option) {
      segments.push(`${gene.name}/${optionState}`);
      return;
    }

    if (option.group === 'rezessiv') {
      if (option.state === 'het') {
        segments.push(`${gene.name}/het`);
      } else {
        segments.push(`${gene.name}/expressed`);
      }
      return;
    }

    if (option.group === 'inkomplett dominant') {
      if (option.state === 'super') {
        segments.push(`${gene.name}/${option.label}`);
      } else {
        segments.push(`${gene.name}/expressed`);
      }
      return;
    }

    if (option.group === 'dominant') {
      segments.push(`${gene.name}/present`);
      return;
    }

    if (option.group === 'polygen') {
      segments.push(option.label);
      return;
    }

    segments.push(option.label);
  });

  return segments.join('; ');
}
