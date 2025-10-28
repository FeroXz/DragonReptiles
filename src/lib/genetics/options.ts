import { GeneDef } from './types.js';

export type OptionGroup = 'recessive' | 'id' | 'dominant' | 'poly';
export type OptionState = 'het' | 'expressed' | 'super' | 'present' | 'poly';

export interface Option {
  geneKey: string;
  state: OptionState;
  label: string;
  keywords: string[];
  group: OptionGroup;
}

const GROUP_MAP: Record<GeneDef['type'], OptionGroup> = {
  recessive: 'recessive',
  incomplete_dominant: 'id',
  dominant: 'dominant',
  polygenic: 'poly'
};

function buildKeywords(gene: GeneDef, base: string, extras: string[] = []): string[] {
  const names = [gene.name, ...(gene.aliases ?? []), ...(gene.searchAliases ?? [])];
  const tokens = [base, ...names, ...extras];
  return Array.from(
    new Set(
      tokens
        .flatMap((token) => token.split(/[\s/-]+/))
        .map((token) => token.trim().toLowerCase())
        .filter(Boolean)
    )
  );
}

export function buildOptions(genes: GeneDef[]): Option[] {
  const options: Option[] = [];

  genes.forEach((gene) => {
    const group = GROUP_MAP[gene.type];
    if (!group) {
      return;
    }

    switch (gene.type) {
      case 'recessive': {
        const hetLabel = `Het ${gene.name}`;
        options.push({
          geneKey: gene.key,
          state: 'het',
          label: hetLabel,
          keywords: buildKeywords(gene, hetLabel, ['het']),
          group
        });
        const expressedLabel = gene.name;
        options.push({
          geneKey: gene.key,
          state: 'expressed',
          label: expressedLabel,
          keywords: buildKeywords(gene, expressedLabel),
          group
        });
        break;
      }
      case 'incomplete_dominant': {
        const expressedLabel = gene.name;
        options.push({
          geneKey: gene.key,
          state: 'expressed',
          label: expressedLabel,
          keywords: buildKeywords(gene, expressedLabel),
          group
        });
        const superLabel = gene.superLabel ?? `Super ${gene.name}`;
        options.push({
          geneKey: gene.key,
          state: 'super',
          label: superLabel,
          keywords: buildKeywords(gene, superLabel, ['super']),
          group
        });
        break;
      }
      case 'dominant': {
        const label = gene.name;
        options.push({
          geneKey: gene.key,
          state: 'present',
          label,
          keywords: buildKeywords(gene, label),
          group
        });
        break;
      }
      case 'polygenic': {
        if (gene.visible === false) {
          break;
        }
        const label = `Linie ${gene.name}`;
        options.push({
          geneKey: gene.key,
          state: 'poly',
          label,
          keywords: buildKeywords(gene, label, ['line', 'linie']),
          group
        });
        break;
      }
      default:
        break;
    }
  });

  return options;
}
