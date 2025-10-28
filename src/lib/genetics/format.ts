import { buildOptions, type Option } from './options.js';
import { GeneDef, Zygosity } from './types.js';

function normalizeToken(token: string): string {
  return token.trim();
}

interface NormalizedEntry {
  state: Zygosity;
  posHet?: number;
}

function normalizeEntry(entry: Zygosity | { state: Zygosity; posHet?: number } | undefined): NormalizedEntry | null {
  if (!entry) {
    return null;
  }
  if (typeof entry === 'string') {
    return { state: entry };
  }
  return { state: entry.state, posHet: entry.posHet };
}

function hasPosHet(entry: NormalizedEntry | null): entry is NormalizedEntry & { posHet: number } {
  return Boolean(entry && typeof entry.posHet === 'number' && entry.posHet > 0 && entry.posHet < 100);
}

function normalizePosHet(value: number): number {
  if (value >= 95) {
    return 100;
  }
  if (value <= 5) {
    return 0;
  }
  const anchors = [33, 50, 66];
  let closest = anchors[0];
  let diff = Math.abs(value - closest);
  for (let i = 1; i < anchors.length; i += 1) {
    const currentDiff = Math.abs(value - anchors[i]);
    if (currentDiff < diff) {
      diff = currentDiff;
      closest = anchors[i];
    }
  }
  return closest;
}

function formatPosHetLabel(gene: GeneDef, value: number): string {
  const rounded = normalizePosHet(value);
  return `${rounded}% Het ${gene.name}`;
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
  if (option.group === 'id') {
    return 1;
  }
  if (option.group === 'recessive' && option.state === 'expressed') {
    return 2;
  }
  if (option.state === 'het') {
    return 3;
  }
  if (option.group === 'dominant') {
    return 4;
  }
  if (option.group === 'poly') {
    return 5;
  }
  return 6;
}

export interface PhenotypeOptions {
  includeHet?: boolean;
}

export function buildPhenotypeTokens(
  genotype: Record<string, Zygosity | { state: Zygosity; posHet?: number }>,
  genes: GeneDef[],
  options: PhenotypeOptions = {}
): string[] {
  const includeHet = options.includeHet ?? true;
  const optionIndex = buildOptionIndex(genes);
  const tokens: Array<{ label: string; priority: number }> = [];

  for (const gene of genes) {
    const entry = normalizeEntry(genotype[gene.key]);
    if (!entry) {
      continue;
    }
    if (hasPosHet(entry)) {
      tokens.push({ label: formatPosHetLabel(gene, entry.posHet), priority: 3.5 });
      continue;
    }
    const state = entry.state;
    if (state === 'normal') {
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

export function formatGenotypeCompact(
  genotype: Record<string, Zygosity | { state: Zygosity; posHet?: number }>,
  genes: GeneDef[]
): string {
  const optionIndex = buildOptionIndex(genes);
  const segments: string[] = [];

  genes.forEach((gene) => {
    const entry = normalizeEntry(genotype[gene.key]);
    if (!entry) {
      return;
    }
    if (hasPosHet(entry)) {
      const label = formatPosHetLabel(gene, entry.posHet);
      segments.push(label);
      return;
    }
    const state = entry.state;
    if (state === 'normal') {
      return;
    }
    const optionState = mapStateToOptionState(gene, state);
    if (!optionState) {
      return;
    }
    const option = optionIndex.get(`${gene.key}:${optionState}`);
    if (!option) {
      segments.push(`${gene.name}/${optionState}`);
      return;
    }

    if (option.group === 'recessive') {
      if (option.state === 'het') {
        segments.push(`${gene.name}/het`);
      } else {
        segments.push(`${gene.name}/expressed`);
      }
      return;
    }

    if (option.group === 'id') {
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

    if (option.group === 'poly') {
      segments.push(option.label);
      return;
    }

    segments.push(option.label);
  });

  return segments.join('; ');
}
