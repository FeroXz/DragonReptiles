import { predictPairing } from './engine.js';
import { GeneDef, ParentGenotype } from './types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';

const hognose = hognoseGenes as GeneDef[];
const pogona = pogonaGenes as GeneDef[];

type ProbabilityIndex = Record<string, number>;

function geneByKey(list: GeneDef[], key: string): GeneDef {
  const gene = list.find((entry) => entry.key === key);
  if (!gene) {
    throw new Error(`Missing gene for key ${key}`);
  }
  return gene;
}

function probabilityMap(results: ReturnType<typeof predictPairing>, geneKey: string): ProbabilityIndex {
  return results.reduce<ProbabilityIndex>((acc, result) => {
    const value = result.genotype[geneKey];
    const key = typeof value === 'string' ? value : value?.state ?? 'normal';
    acc[key] = (acc[key] ?? 0) + result.probability;
    return acc;
  }, {});
}

describe('predictPairing', () => {
  it('computes recessive het x het ratios', () => {
    const genes = [geneByKey(hognose, 'albino')];
    const parent: ParentGenotype = { albino: 'het' };
    const results = predictPairing(parent, parent, genes);
    const probabilities = probabilityMap(results, 'albino');

    expect(results).toHaveLength(3);
    expect(probabilities.expressed).toBeCloseTo(0.25, 5);
    expect(probabilities.het).toBeCloseTo(0.5, 5);
    expect(probabilities.normal).toBeCloseTo(0.25, 5);

    const homoTokens = results.find((result) => result.genotype.albino === 'expressed')?.phenotypeTokens ?? [];
    expect(homoTokens).toEqual(['Albino']);
    const hetTokens = results.find((result) => result.genotype.albino === 'het')?.phenotypeTokens ?? [];
    expect(hetTokens).toEqual(['Het Albino']);
  });

  it('labels incomplete dominant super forms', () => {
    const genes = [geneByKey(hognose, 'anaconda')];
    const parent: ParentGenotype = { anaconda: 'expressed' };
    const results = predictPairing(parent, parent, genes);
    const probabilities = probabilityMap(results, 'anaconda');

    expect(probabilities.super).toBeCloseTo(0.25, 5);
    expect(probabilities.expressed).toBeCloseTo(0.5, 5);
    expect(probabilities.normal).toBeCloseTo(0.25, 5);

    const superTokens = results.find((result) => result.genotype.anaconda === 'super')?.phenotypeTokens ?? [];
    expect(superTokens).toEqual(['Superconda']);
  });

  it('handles Arctic x Super Arctic', () => {
    const genes = [geneByKey(hognose, 'arctic')];
    const parentA: ParentGenotype = { arctic: 'expressed' };
    const parentB: ParentGenotype = { arctic: 'super' };
    const results = predictPairing(parentA, parentB, genes);
    const probabilities = probabilityMap(results, 'arctic');

    expect(probabilities.super).toBeCloseTo(0.5, 5);
    expect(probabilities.expressed).toBeCloseTo(0.5, 5);
    const superTokens = results.find((result) => result.genotype.arctic === 'super')?.phenotypeTokens ?? [];
    expect(superTokens).toEqual(['Super Arctic']);
  });

  it('produces Silkback label for Leatherback supers', () => {
    const genes = [geneByKey(pogona, 'leatherback')];
    const parent: ParentGenotype = { leatherback: 'expressed' };
    const results = predictPairing(parent, parent, genes);
    const probabilities = probabilityMap(results, 'leatherback');

    expect(probabilities.super).toBeCloseTo(0.25, 5);
    expect(probabilities.expressed).toBeCloseTo(0.5, 5);
    expect(probabilities.normal).toBeCloseTo(0.25, 5);

    const superTokens = results.find((result) => result.genotype.leatherback === 'super')?.phenotypeTokens ?? [];
    expect(superTokens).toEqual(['Silkback']);
  });

  it('computes recessive ratios for pogona hypo', () => {
    const genes = [geneByKey(pogona, 'hypo')];
    const parent: ParentGenotype = { hypo: 'het' };
    const results = predictPairing(parent, parent, genes);
    const probabilities = probabilityMap(results, 'hypo');

    expect(probabilities.expressed).toBeCloseTo(0.25, 5);
    expect(probabilities.het).toBeCloseTo(0.5, 5);
    expect(probabilities.normal).toBeCloseTo(0.25, 5);
  });

  it('supports posHet probabilities', () => {
    const genes = [geneByKey(hognose, 'albino')];
    const parentA: ParentGenotype = { albino: { state: 'het', posHet: 66 } };
    const parentB: ParentGenotype = { albino: 'normal' };
    const results = predictPairing(parentA, parentB, genes);
    const probabilities = probabilityMap(results, 'albino');

    expect(probabilities.het).toBeCloseTo(0.33, 2);
    expect(probabilities.normal).toBeCloseTo(0.67, 2);
    expect(probabilities.expressed ?? 0).toBeCloseTo(0, 5);
  });
});
