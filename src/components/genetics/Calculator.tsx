import { useEffect, useMemo, useState } from 'react';
import clsx from 'clsx';
import { predictPairing } from '@lib/genetics/engine.js';
import { GeneDef, ParentGenotype, PairingResult } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import { GenotypeSearch } from './GenotypeSearch.js';
import { ResultFilters, ResultList, ResultListTranslations } from './ResultList.js';
import translationsDe from '@i18n/genetics.de.json';

interface SpeciesOption {
  key: 'hognose' | 'pogona';
  label: string;
  subtitle: string;
  genes: GeneDef[];
}

type SectionKey = 'incomplete_dominant' | 'dominant' | 'recessive' | 'polygenic';

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
  sectionTitles: Record<SectionKey, string>;
  polygenicHint: string;
  speciesHeading: string;
  speciesHint: string;
  noGenes: string;
  actionHint: string;
  headingResults: string;
  filterSuper: string;
  filterHighProbability: string;
  filterShowHet: string;
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

const resultTranslations: ResultListTranslations = {
  heading: messages.headingResults,
  filterSuper: messages.filterSuper,
  filterHighProbability: messages.filterHighProbability,
  filterShowHet: messages.filterShowHet,
  notCalculated: messages.notCalculated,
  empty: messages.empty,
  remainder: messages.remainder,
  normalForm: messages.normalForm
};

const generalText = {
  speciesHeading: messages.speciesHeading,
  speciesHint: messages.speciesHint,
  parentA: messages.parentA,
  parentB: messages.parentB,
  calculate: messages.calculate,
  reset: messages.reset,
  noGenes: messages.noGenes,
  actionHint: messages.actionHint
};

export function Calculator() {
  const [activeSpeciesKey, setActiveSpeciesKey] = useState<SpeciesOption['key']>(SPECIES[0].key);
  const [parentA, setParentA] = useState<ParentGenotype>({});
  const [parentB, setParentB] = useState<ParentGenotype>({});
  const [results, setResults] = useState<PairingResult[]>([]);
  const [remainderProbability, setRemainderProbability] = useState(0);
  const [calculated, setCalculated] = useState(false);
  const [filters, setFilters] = useState<ResultFilters>({ superOnly: false, topProbability: false, showHet: true });

  const activeSpecies = useMemo(
    () => SPECIES.find((species) => species.key === activeSpeciesKey) ?? SPECIES[0],
    [activeSpeciesKey]
  );

  useEffect(() => {
    setParentA({});
    setParentB({});
    setResults([]);
    setRemainderProbability(0);
    setCalculated(false);
  }, [activeSpeciesKey]);

  const handleCalculate = () => {
    if (!activeSpecies.genes.length) {
      setResults([]);
      setRemainderProbability(0);
      setCalculated(true);
      return;
    }
    const prediction = predictPairing(parentA, parentB, activeSpecies.genes);
    const sorted = [...prediction].sort((a, b) => b.probability - a.probability);
    const topTwelve = sorted.slice(0, 12);
    const remainder = sorted.slice(12).reduce((sum, entry) => sum + entry.probability, 0);
    setResults(topTwelve);
    setRemainderProbability(remainder);
    setCalculated(true);
  };

  const handleReset = () => {
    setParentA({});
    setParentB({});
    setResults([]);
    setRemainderProbability(0);
    setCalculated(false);
  };

  const toggleFilter = (filterKey: keyof ResultFilters) => {
    setFilters((prev) => ({
      ...prev,
      [filterKey]: !prev[filterKey]
    }));
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
              onClick={() => setActiveSpeciesKey(species.key)}
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

      <ResultList
        results={results}
        genes={activeSpecies.genes}
        filters={filters}
        onToggleFilter={toggleFilter}
        remainderProbability={remainderProbability}
        calculated={calculated}
        translations={resultTranslations}
      />
    </div>
  );
}

export default Calculator;
