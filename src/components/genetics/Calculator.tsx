import { useEffect, useMemo, useState } from 'react';
import clsx from 'clsx';
import { predictPairing } from '@lib/genetics/engine.js';
import { GeneDef, ParentGenotype, PairingResult, Zygosity } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import { GenotypeSearch } from './GenotypeSearch.js';
import { ResultTable } from './ResultTable.js';
import morphAliases from '@data/morph-aliases.json';
import translationsDe from '@i18n/genetics.de.json';
import '../../styles/genetics.css';

interface SpeciesOption {
  key: 'hognose' | 'pogona';
  label: string;
  subtitle: string;
  genes: GeneDef[];
}

interface GeneticsMessages {
  reset: string;
  calculate: string;
  parentA: string;
  parentB: string;
  normal: string;
  het: string;
  expressed: string;
  super: string;
  present: string;
  posHet: string;
  posHetHelper: string;
  warningIncompatible: string;
  polygenicHint: string;
  speciesHeading: string;
  speciesHint: string;
  noGenes: string;
  actionHint: string;
  headingResults: string;
  notCalculated: string;
  empty: string;
  remainder: string;
  normalForm: string;
  species: Record<'hognose' | 'pogona', { label: string; subtitle: string }>;
}

const messages = translationsDe as GeneticsMessages;

const SPECIES: SpeciesOption[] = [
  {
    key: 'hognose',
    label: messages.species.hognose.label,
    subtitle: messages.species.hognose.subtitle,
    genes: (hognoseGenes as GeneDef[]).map((gene) => ({ ...gene }))
  },
  {
    key: 'pogona',
    label: messages.species.pogona.label,
    subtitle: messages.species.pogona.subtitle,
    genes: (pogonaGenes as GeneDef[]).map((gene) => ({ ...gene }))
  }
];

const generalText = {
  speciesHeading: messages.speciesHeading,
  speciesHint: messages.speciesHint,
  parentA: messages.parentA,
  parentB: messages.parentB,
  calculate: messages.calculate,
  reset: messages.reset,
  noGenes: messages.noGenes,
  actionHint: messages.actionHint,
  headingResults: messages.headingResults,
  notCalculated: messages.notCalculated,
  empty: messages.empty,
  remainder: messages.remainder
};

type MorphAliasEntry = { name: string; genes: string[] };

const MORPH_ALIASES = morphAliases as Record<'hognose' | 'pogona', MorphAliasEntry[]>;

function toZygosity(entry: ParentGenotype[string]): Zygosity | undefined {
  if (!entry) {
    return undefined;
  }
  if (typeof entry === 'string') {
    return entry;
  }
  return entry.state;
}

function encodeState(gene: GeneDef, state: Zygosity): string | null {
  if (gene.type === 'recessive') {
    if (state === 'het') {
      return 'het';
    }
    if (state === 'expressed') {
      return 'expressed';
    }
    return null;
  }
  if (gene.type === 'incomplete_dominant') {
    if (state === 'super') {
      return 'super';
    }
    if (state === 'expressed') {
      return 'expressed';
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

function decodeState(gene: GeneDef, token: string | undefined): Zygosity | null {
  const normalized = token?.toLowerCase();
  if (!normalized || normalized === 'expressed') {
    if (gene.type === 'recessive' || gene.type === 'incomplete_dominant' || gene.type === 'dominant' || gene.type === 'polygenic') {
      return 'expressed';
    }
    return null;
  }
  if (normalized === 'het' && gene.type === 'recessive') {
    return 'het';
  }
  if (normalized === 'super' && gene.type === 'incomplete_dominant') {
    return 'super';
  }
  if (normalized === 'present' && gene.type === 'dominant') {
    return 'expressed';
  }
  if (normalized === 'poly' && gene.type === 'polygenic') {
    return 'expressed';
  }
  if (normalized === 'expressed') {
    return 'expressed';
  }
  return null;
}

function encodeParent(genotype: ParentGenotype, genes: GeneDef[]): string {
  const parts: string[] = [];
  genes.forEach((gene) => {
    const state = toZygosity(genotype[gene.key]);
    if (!state || state === 'normal') {
      return;
    }
    const encoded = encodeState(gene, state);
    if (!encoded) {
      return;
    }
    parts.push(`${gene.key},${encoded}`);
  });
  return parts.join(';');
}

function geneMatchesIdentifier(gene: GeneDef, identifier: string): boolean {
  const normalized = identifier.trim().toLowerCase();
  if (gene.key.toLowerCase() === normalized) {
    return true;
  }
  if (gene.aliases?.some((alias) => alias.toLowerCase() === normalized)) {
    return true;
  }
  if (gene.searchAliases?.some((alias) => alias.toLowerCase() === normalized)) {
    return true;
  }
  return false;
}

function decodeParent(serialized: string | null, genes: GeneDef[]): ParentGenotype {
  if (!serialized) {
    return {};
  }
  const entries = serialized.split(';').map((entry) => entry.trim()).filter(Boolean);
  if (!entries.length) {
    return {};
  }
  const value: ParentGenotype = {};
  entries.forEach((entry) => {
    const [identifier, rawState] = entry.split(',');
    if (!identifier) {
      return;
    }
    const gene = genes.find((candidate) => geneMatchesIdentifier(candidate, identifier));
    if (!gene) {
      return;
    }
    const state = decodeState(gene, rawState?.trim());
    if (!state) {
      return;
    }
    value[gene.key] = state;
  });
  return value;
}

export function Calculator() {
  const [activeSpeciesKey, setActiveSpeciesKey] = useState<SpeciesOption['key']>(SPECIES[0].key);
  const [parentA, setParentA] = useState<ParentGenotype>({});
  const [parentB, setParentB] = useState<ParentGenotype>({});
  const [results, setResults] = useState<PairingResult[]>([]);
  const [remainderProbability, setRemainderProbability] = useState(0);
  const [calculated, setCalculated] = useState(false);
  const [initialized, setInitialized] = useState(false);

  const activeSpecies = useMemo(
    () => SPECIES.find((species) => species.key === activeSpeciesKey) ?? SPECIES[0],
    [activeSpeciesKey]
  );

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const speciesParam = params.get('s');
    const resolvedSpecies = speciesParam === 'hognose' || speciesParam === 'pogona' ? speciesParam : activeSpeciesKey;
    if (resolvedSpecies !== activeSpeciesKey) {
      setActiveSpeciesKey(resolvedSpecies);
    }
    const targetSpecies = SPECIES.find((option) => option.key === resolvedSpecies) ?? activeSpecies;
    const decodedA = decodeParent(params.get('a'), targetSpecies.genes);
    const decodedB = decodeParent(params.get('b'), targetSpecies.genes);
    if (Object.keys(decodedA).length) {
      setParentA(decodedA);
    }
    if (Object.keys(decodedB).length) {
      setParentB(decodedB);
    }
    setInitialized(true);
  }, []);

  useEffect(() => {
    if (!initialized) {
      return;
    }
    const params = new URLSearchParams();
    params.set('s', activeSpeciesKey);
    const encodedA = encodeParent(parentA, activeSpecies.genes);
    const encodedB = encodeParent(parentB, activeSpecies.genes);
    if (encodedA) {
      params.set('a', encodedA);
    }
    if (encodedB) {
      params.set('b', encodedB);
    }
    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
    window.history.replaceState({}, '', nextUrl);
  }, [activeSpeciesKey, activeSpecies.genes, parentA, parentB, initialized]);

  const resetCalculation = () => {
    setResults([]);
    setRemainderProbability(0);
    setCalculated(false);
  };

  const handleCalculate = () => {
    if (!activeSpecies.genes.length) {
      resetCalculation();
      setCalculated(true);
      return;
    }
    const prediction = predictPairing(parentA, parentB, activeSpecies.genes);
    const sorted = [...prediction].sort((a, b) => b.probability - a.probability);
    const topResults = sorted.slice(0, 50);
    const remainder = sorted.slice(50).reduce((sum, entry) => sum + entry.probability, 0);
    setResults(topResults);
    setRemainderProbability(remainder);
    setCalculated(true);
  };

  const handleReset = () => {
    setParentA({});
    setParentB({});
    resetCalculation();
  };

  const handleSelectSpecies = (key: SpeciesOption['key']) => {
    if (key === activeSpeciesKey) {
      return;
    }
    setActiveSpeciesKey(key);
    setParentA({});
    setParentB({});
    resetCalculation();
  };

  return (
    <div className="genetics-calculator">
      <section className="genetics-calculator__species">
        <header>
          <h2>{generalText.speciesHeading}</h2>
          <p>{generalText.speciesHint}</p>
        </header>
        <div className="species-selector" role="group" aria-label={generalText.speciesHeading}>
          {SPECIES.map((species) => (
            <button
              key={species.key}
              type="button"
              className={clsx('species-chip', { 'is-active': species.key === activeSpecies.key })}
              onClick={() => handleSelectSpecies(species.key)}
            >
              <span className="species-chip__label">{species.label}</span>
              <span className="species-chip__subtitle">{species.subtitle}</span>
            </button>
          ))}
        </div>
      </section>

      {!activeSpecies.genes.length ? (
        <p className="genetics-calculator__empty">{generalText.noGenes}</p>
      ) : (
        <div className="genetics-calculator__grid">
          <div className="genotype-panel">
            <header>
              <h2>{generalText.parentA}</h2>
            </header>
            <GenotypeSearch species={activeSpecies.key} value={parentA} onChange={setParentA} />
          </div>
          <div className="genotype-panel">
            <header>
              <h2>{generalText.parentB}</h2>
            </header>
            <GenotypeSearch species={activeSpecies.key} value={parentB} onChange={setParentB} />
          </div>
        </div>
      )}

      <div className="genetics-actionbar" aria-label={generalText.actionHint}>
        <button type="button" className="btn-secondary" onClick={handleReset}>
          {generalText.reset}
        </button>
        <button type="button" className="btn" onClick={handleCalculate} disabled={!activeSpecies.genes.length}>
          {generalText.calculate}
        </button>
      </div>

      <ResultTable
        results={results}
        genes={activeSpecies.genes}
        calculated={calculated}
        remainderProbability={remainderProbability}
        emptyText={generalText.empty}
        heading={generalText.headingResults}
        notCalculatedText={generalText.notCalculated}
        remainderTextTemplate={generalText.remainder}
        morphAliases={MORPH_ALIASES[activeSpecies.key] ?? []}
      />
    </div>
  );
}

export default Calculator;
