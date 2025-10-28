import { buildPhenotypeTokens } from './format.js';
import { GeneDef, ParentGenotype, PairingResult, Zygosity } from './types.js';

type ParentEntry = { state: Zygosity; posHet?: number };

const EPSILON = 1e-9;

function clampProbability(value: number): number {
  if (!Number.isFinite(value) || Number.isNaN(value)) {
    return 0;
  }
  if (value < 0) {
    return 0;
  }
  if (value > 1) {
    return 1;
  }
  return value;
}

function normalizeEntry(value: ParentGenotype[string]): ParentEntry {
  if (!value) {
    return { state: 'normal' };
  }
  if (typeof value === 'string') {
    return { state: value };
  }
  return { state: value.state, posHet: value.posHet };
}

function posHetFraction(entry: ParentEntry): number {
  if (entry.posHet === undefined) {
    return 1;
  }
  return clampProbability(entry.posHet / 100);
}

function mutatedAlleleProbability(gene: GeneDef, entry: ParentEntry): number {
  switch (gene.type) {
    case 'recessive': {
      if (entry.state === 'expressed') {
        return 1;
      }
      if (entry.state === 'het') {
        return 0.5 * posHetFraction(entry);
      }
      if (entry.state === 'normal' && entry.posHet !== undefined) {
        return 0.5 * posHetFraction(entry);
      }
      return 0;
    }
    case 'incomplete_dominant': {
      if (entry.state === 'super') {
        return 1;
      }
      if (entry.state === 'expressed') {
        return 0.5;
      }
      return 0;
    }
    case 'dominant': {
      if (entry.state === 'expressed') {
        return 0.5;
      }
      return 0;
    }
    default:
      return 0;
  }
}

interface StateProbability {
  state: Zygosity;
  probability: number;
}

function geneStateProbabilities(gene: GeneDef, parentA: ParentEntry, parentB: ParentEntry): StateProbability[] {
  if (gene.type === 'polygenic') {
    return [{ state: parentA.state === 'expressed' || parentB.state === 'expressed' ? 'expressed' : 'normal', probability: 1 }];
  }

  const mutatedA = mutatedAlleleProbability(gene, parentA);
  const mutatedB = mutatedAlleleProbability(gene, parentB);

  const probSuper = clampProbability(mutatedA * mutatedB);
  const probNormal = clampProbability((1 - mutatedA) * (1 - mutatedB));
  const probSingle = clampProbability(1 - probSuper - probNormal);

  switch (gene.type) {
    case 'recessive':
      return [
        { state: 'expressed', probability: probSuper },
        { state: 'het', probability: probSingle },
        { state: 'normal', probability: probNormal }
      ];
    case 'incomplete_dominant':
      return [
        { state: 'super', probability: probSuper },
        { state: 'expressed', probability: probSingle },
        { state: 'normal', probability: probNormal }
      ];
    case 'dominant': {
      const expressedProbability = clampProbability(1 - probNormal);
      return [
        { state: 'expressed', probability: expressedProbability },
        { state: 'normal', probability: probNormal }
      ];
    }
    default:
      return [{ state: 'normal', probability: 1 }];
  }
}

function buildPolygenicGenotype(
  parentA: ParentGenotype,
  parentB: ParentGenotype,
  polyGenes: GeneDef[]
): Record<string, Zygosity> {
  const genotype: Record<string, Zygosity> = {};
  polyGenes.forEach((gene) => {
    const entryA = normalizeEntry(parentA[gene.key]);
    const entryB = normalizeEntry(parentB[gene.key]);
    genotype[gene.key] = entryA.state === 'expressed' || entryB.state === 'expressed' ? 'expressed' : 'normal';
  });
  return genotype;
}

export function predictPairing(
  parentA: ParentGenotype,
  parentB: ParentGenotype,
  genes: GeneDef[]
): PairingResult[] {
  if (!genes.length) {
    return [];
  }

  const polyGenes = genes.filter((gene) => gene.type === 'polygenic');
  const standardGenes = genes.filter((gene) => gene.type !== 'polygenic');

  let combinations: Array<{ probability: number; genotype: Record<string, Zygosity>; key: string }> = [
    { probability: 1, genotype: {}, key: '' }
  ];

  standardGenes.forEach((gene) => {
    const entryA = normalizeEntry(parentA[gene.key]);
    const entryB = normalizeEntry(parentB[gene.key]);
    const states = geneStateProbabilities(gene, entryA, entryB).filter((item) => item.probability > EPSILON);

    const next = new Map<string, { probability: number; genotype: Record<string, Zygosity> }>();
    combinations.forEach((combo) => {
      states.forEach((state) => {
        const probability = combo.probability * state.probability;
        if (probability <= EPSILON) {
          return;
        }
        const genotype = { ...combo.genotype, [gene.key]: state.state };
        const key = `${combo.key}|${gene.key}:${state.state}`;
        const existing = next.get(key);
        if (existing) {
          existing.probability += probability;
        } else {
          next.set(key, { probability, genotype });
        }
      });
    });
    combinations = Array.from(next.entries()).map(([key, value]) => ({ key, ...value }));
  });

  const polyGenotype = buildPolygenicGenotype(parentA, parentB, polyGenes);

  const results: PairingResult[] = combinations
    .map((combo) => {
      const genotype: Record<string, Zygosity> = { ...polyGenotype };
      standardGenes.forEach((gene) => {
        genotype[gene.key] = combo.genotype[gene.key] ?? 'normal';
      });
      const phenotypeTokens = buildPhenotypeTokens(genotype, genes);
      return {
        probability: combo.probability,
        genotype,
        phenotypeTokens
      };
    })
    .filter((result) => result.probability > EPSILON)
    .sort((a, b) => b.probability - a.probability);

  return results;
}
