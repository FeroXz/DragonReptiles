import { useMemo, useState } from 'react';
import clsx from 'clsx';
import { predictPairing } from '@lib/genetics/engine.js';
import { GeneDef, ParentGenotype, PairingResult } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import translationsDe from '@i18n/genetics.de.json';
import { ParentPicker } from './ParentPicker.js';
import { ResultList } from './ResultList.js';
import '../../styles/genetics.css';

interface Messages {
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
  polygenicHint: string;
  warningIncompatible: string;
  filterSuper: string;
  filterHighProbability: string;
  filterShowHet: string;
  filterLabel: string;
  notCalculated: string;
  empty: string;
  remainder: string;
  normalForm: string;
  speciesHeading: string;
  speciesHint: string;
  sectionTitles: Record<'recessive' | 'incomplete_dominant' | 'dominant' | 'polygenic', string>;
  species: Record<'hognose' | 'pogona', { label: string; subtitle: string }>;
}

type SpeciesKey = 'hognose' | 'pogona';

const messages = translationsDe as Messages;

interface SpeciesOption {
  key: SpeciesKey;
  label: string;
  subtitle: string;
  genes: GeneDef[];
}

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

interface FilterState {
  superOnly: boolean;
  highProbability: boolean;
  showHet: boolean;
}

const DEFAULT_FILTERS: FilterState = {
  superOnly: false,
  highProbability: false,
  showHet: true
};

export function Calculator() {
  const [speciesKey, setSpeciesKey] = useState<SpeciesKey>('hognose');
  const [parentA, setParentA] = useState<ParentGenotype>({});
  const [parentB, setParentB] = useState<ParentGenotype>({});
  const [results, setResults] = useState<PairingResult[] | null>(null);
  const [filters, setFilters] = useState<FilterState>(DEFAULT_FILTERS);

  const species = useMemo(() => SPECIES.find((entry) => entry.key === speciesKey) ?? SPECIES[0], [speciesKey]);

  const handleSpeciesChange = (key: SpeciesKey) => {
    setSpeciesKey(key);
    setParentA({});
    setParentB({});
    setResults(null);
    setFilters(DEFAULT_FILTERS);
  };

  const handleCalculate = () => {
    const resultList = predictPairing(parentA, parentB, species.genes);
    setResults(resultList);
  };

  const handleReset = () => {
    setParentA({});
    setParentB({});
    setResults(null);
    setFilters(DEFAULT_FILTERS);
  };

  const activeResults = useMemo(() => {
    if (!results) {
      return [];
    }
    const sorted = [...results].sort((a, b) => b.probability - a.probability);
    const top = sorted.slice(0, 12);
    return top;
  }, [results]);

  const remainderProbability = useMemo(() => {
    if (!results) {
      return 0;
    }
    const sumTop = activeResults.reduce((acc, result) => acc + result.probability, 0);
    const total = results.reduce((acc, result) => acc + result.probability, 0);
    const remainder = Math.max(total - sumTop, 0);
    return remainder > 0.0001 ? remainder : 0;
  }, [activeResults, results]);

  const hasResults = results !== null;

  return (
    <div className="genetics-calculator">
      <section className="genetics-calculator__species">
        <header>
          <h2>{messages.speciesHeading}</h2>
          <p>{messages.speciesHint}</p>
        </header>
        <div className="genetics-calculator__species-list">
          {SPECIES.map((entry) => (
            <button
              type="button"
              key={entry.key}
              className={clsx('chip-button', { 'is-active': entry.key === species.key })}
              onClick={() => handleSpeciesChange(entry.key)}
            >
              <span className="chip-button__label">{entry.label}</span>
              <span className="chip-button__subtitle">{entry.subtitle}</span>
            </button>
          ))}
        </div>
      </section>
      <div className="genetics-calculator__parents">
        <ParentPicker
          label={messages.parentA}
          genes={species.genes}
          value={parentA}
          onChange={setParentA}
          messages={{
            normal: messages.normal,
            het: messages.het,
            expressed: messages.expressed,
            super: messages.super,
            present: messages.present,
            posHet: messages.posHet,
            polygenicHint: messages.polygenicHint,
            warningIncompatible: messages.warningIncompatible,
            sectionTitles: messages.sectionTitles
          }}
        />
        <ParentPicker
          label={messages.parentB}
          genes={species.genes}
          value={parentB}
          onChange={setParentB}
          messages={{
            normal: messages.normal,
            het: messages.het,
            expressed: messages.expressed,
            super: messages.super,
            present: messages.present,
            posHet: messages.posHet,
            polygenicHint: messages.polygenicHint,
            warningIncompatible: messages.warningIncompatible,
            sectionTitles: messages.sectionTitles
          }}
        />
      </div>
      <ResultList
        results={activeResults}
        genes={species.genes}
        filters={filters}
        onFiltersChange={setFilters}
        remainderProbability={remainderProbability}
        messages={{
          filterSuper: messages.filterSuper,
          filterHighProbability: messages.filterHighProbability,
          filterShowHet: messages.filterShowHet,
          filterLabel: messages.filterLabel,
          empty: hasResults ? messages.empty : messages.notCalculated,
          remainder: messages.remainder,
          normalForm: messages.normalForm
        }}
      />
      <div className="genetics-actionbar">
        <button type="button" className="btn btn-secondary" onClick={handleReset}>
          {messages.reset}
        </button>
        <button type="button" className="btn" onClick={handleCalculate}>
          {messages.calculate}
        </button>
      </div>
    </div>
  );
}
