import { buildPhenotypeTokens, formatGenotypeCompact, formatPhenotype } from './format.js';
import { GeneDef, Zygosity } from './types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';

const hognose = hognoseGenes as GeneDef[];
const pogona = pogonaGenes as GeneDef[];

function geneByKey(list: GeneDef[], key: string): GeneDef {
  const gene = list.find((entry) => entry.key === key);
  if (!gene) {
    throw new Error(`Missing gene for key ${key}`);
  }
  return gene;
}

describe('genetics format helpers', () => {
  it('builds ordered phenotype tokens using option labels', () => {
    const genes = [
      geneByKey(hognose, 'anaconda'),
      geneByKey(hognose, 'arctic'),
      geneByKey(hognose, 'albino'),
      geneByKey(hognose, 'hypo'),
      geneByKey(pogona, 'dunner'),
      geneByKey(pogona, 'color_red')
    ];
    const genotype: Record<string, Zygosity> = {
      anaconda: 'super',
      arctic: 'expressed',
      albino: 'expressed',
      hypo: 'het',
      dunner: 'expressed',
      color_red: 'expressed'
    };

    const tokens = buildPhenotypeTokens(genotype, genes);

    expect(tokens).toEqual([
      'Superconda',
      'Arctic',
      'Albino',
      'het Hypo',
      'Dunner',
      'Linie Red Line'
    ]);
  });

  it('deduplicates phenotype output when formatting', () => {
    const formatted = formatPhenotype(['Albino', 'Albino', 'het Hypo']);
    expect(formatted).toBe('Albino het Hypo');
  });

  it('formats compact genotypes with super labels and poly lines', () => {
    const genes = [
      geneByKey(hognose, 'anaconda'),
      geneByKey(hognose, 'albino'),
      geneByKey(pogona, 'color_red')
    ];
    const genotype: Record<string, Zygosity> = {
      anaconda: 'super',
      albino: 'het',
      color_red: 'expressed'
    };

    const compact = formatGenotypeCompact(genotype, genes);
    expect(compact).toBe('Anaconda/Superconda; Albino/het; Linie Red Line');
  });
});
