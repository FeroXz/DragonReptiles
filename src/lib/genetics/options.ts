import { GeneDef } from './types.js';

export type Option = {
  geneKey: string;
  state: 'het' | 'expressed' | 'super' | 'present' | 'poly';
  label: string;
  keywords: string[];
  group: 'recessive' | 'id' | 'dominant' | 'poly';
};

function normalizeKeyword(value: string): string {
  return value.trim().toLowerCase();
}

function collectKeywords(gene: GeneDef, extras: string[] = []): string[] {
  const raw = [gene.name, ...(gene.aliases ?? []), ...(gene.searchAliases ?? []), ...extras];
  const keywords = new Set<string>();
  raw.forEach((entry) => {
    const normalized = normalizeKeyword(entry);
    if (!normalized) {
      return;
    }
    keywords.add(normalized);
    normalized.split(/\s+/).forEach((part) => {
      const partNormalized = normalizeKeyword(part);
      if (partNormalized) {
        keywords.add(partNormalized);
      }
    });
  });
  return Array.from(keywords);
}

export function buildOptions(genes: GeneDef[]): Option[] {
  const opts: Option[] = [];

  for (const gene of genes) {
    if (gene.type === 'recessive') {
      opts.push({
        geneKey: gene.key,
        state: 'het',
        label: `het ${gene.name}`,
        keywords: collectKeywords(gene, ['het']),
        group: 'recessive'
      });
      opts.push({
        geneKey: gene.key,
        state: 'expressed',
        label: gene.name,
        keywords: collectKeywords(gene),
        group: 'recessive'
      });
      continue;
    }

    if (gene.type === 'incomplete_dominant') {
      opts.push({
        geneKey: gene.key,
        state: 'expressed',
        label: gene.name,
        keywords: collectKeywords(gene),
        group: 'id'
      });
      const superLabel = gene.superLabel || `Super ${gene.name}`;
      opts.push({
        geneKey: gene.key,
        state: 'super',
        label: superLabel,
        keywords: collectKeywords(gene, ['super', superLabel]),
        group: 'id'
      });
      continue;
    }

    if (gene.type === 'dominant') {
      opts.push({
        geneKey: gene.key,
        state: 'present',
        label: gene.name,
        keywords: collectKeywords(gene),
        group: 'dominant'
      });
      continue;
    }

    if (gene.type === 'polygenic' && gene.visible !== false) {
      const label = `Linie ${gene.name}`;
      opts.push({
        geneKey: gene.key,
        state: 'poly',
        label,
        keywords: collectKeywords(gene, ['linie', label]),
        group: 'poly'
      });
      continue;
    }
  }

  return opts;
}
