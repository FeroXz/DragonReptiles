import { GeneDef } from './types';

export type Option = {
  geneKey: string;
  state: 'het' | 'expressed' | 'super' | 'present' | 'poly';
  label: string;
  keywords: string[];
  group: 'rezessiv' | 'inkomplett dominant' | 'dominant' | 'polygen';
};

export function buildOptions(genes: GeneDef[]): Option[] {
  const opts: Option[] = [];
  for (const g of genes) {
    const base = [g.name, ...(g.aliases || []), ...(g.searchAliases || [])].map((s) => s.toLowerCase());
    if (g.type === 'recessive') {
      opts.push({
        geneKey: g.key,
        state: 'het',
        label: `het ${g.name}`,
        keywords: ['het', ...base],
        group: 'rezessiv'
      });
      opts.push({
        geneKey: g.key,
        state: 'expressed',
        label: g.name,
        keywords: [...base],
        group: 'rezessiv'
      });
    } else if (g.type === 'incomplete_dominant') {
      opts.push({
        geneKey: g.key,
        state: 'expressed',
        label: g.name,
        keywords: [...base],
        group: 'inkomplett dominant'
      });
      const superLabel = g.superLabel || `Super ${g.name}`;
      opts.push({
        geneKey: g.key,
        state: 'super',
        label: superLabel,
        keywords: ['super', ...base],
        group: 'inkomplett dominant'
      });
    } else if (g.type === 'dominant') {
      opts.push({
        geneKey: g.key,
        state: 'present',
        label: g.name,
        keywords: [...base],
        group: 'dominant'
      });
    } else if (g.type === 'polygenic' && g.visible !== false) {
      opts.push({
        geneKey: g.key,
        state: 'poly',
        label: `Linie ${g.name}`,
        keywords: ['linie', ...base],
        group: 'polygen'
      });
    }
  }
  return opts;
}
