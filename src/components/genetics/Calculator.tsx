import { useCallback, useEffect, useMemo, useState } from 'react';
import clsx from 'clsx';
import { GenotypeSearch } from './GenotypeSearch.js';
import { ResultTable } from './ResultTable.js';
import { predictPairing } from '@lib/genetics/engine.js';
import { getGenesForSpecies } from '@lib/genetics/species.js';
import { ParentGenotype, PairingResult, SpeciesKey } from '@lib/genetics/types.js';
import translationsDe from '@i18n/genetics.de.json';
import morphAliases from '@data/morph-aliases.json';
import '../../styles/genetics.css';

interface Messages {
  reset: string;
  calculate: string;
  parentA: string;
  parentB: string;
  speciesHeading: string;
  speciesHint: string;
  species: Record<SpeciesKey, { label: string; subtitle: string }>;
}

const messages = translationsDe as Messages;

interface SpeciesOption {
  key: SpeciesKey;
  label: string;
  subtitle: string;
}

const SPECIES: SpeciesOption[] = [
  {
    key: 'hognose',
    label: messages.species.hognose.label,
    subtitle: messages.species.hognose.subtitle
  },
  {
    key: 'pogona',
    label: messages.species.pogona.label,
    subtitle: messages.species.pogona.subtitle
  }
];

function normalizeState(value: ParentGenotype[string]): ParentGenotype[string] | undefined {
  if (!value) {
    return undefined;
  }
  if (typeof value === 'string') {
    return value === 'normal' ? undefined : value;
  }
  if (value.state === 'normal') {
    return undefined;
  }
  return { state: value.state };
}

function parseParent(query: string | null, allowed: Set<string>): ParentGenotype {
  if (!query) {
    return {};
  }
  const entries = query.split(';');
  const result: ParentGenotype = {};
  entries.forEach((entry) => {
    const [geneKey, state] = entry.split(',');
    if (!geneKey || !state || !allowed.has(geneKey)) {
      return;
    }
    if (state === 'het' || state === 'expressed' || state === 'super') {
      result[geneKey] = state;
    }
  });
  return result;
}

function encodeParent(parent: ParentGenotype, allowed: Set<string>): string {
  const parts: string[] = [];
  allowed.forEach((geneKey) => {
    const entry = normalizeState(parent[geneKey]);
    if (!entry) {
      return;
    }
    if (typeof entry === 'string') {
      parts.push(`${geneKey},${entry}`);
    } else if (entry.state !== 'normal') {
      parts.push(`${geneKey},${entry.state}`);
    }
  });
  return parts.join(';');
}

export function Calculator() {
  const [speciesKey, setSpeciesKey] = useState<SpeciesKey>('hognose');
  const [parentA, setParentA] = useState<ParentGenotype>({});
  const [parentB, setParentB] = useState<ParentGenotype>({});
  const [results, setResults] = useState<PairingResult[] | null>(null);
  const [hydrated, setHydrated] = useState(false);
  const [shareMessage, setShareMessage] = useState<string | null>(null);
  const [sharePending, setSharePending] = useState(false);

  const genes = useMemo(() => getGenesForSpecies(speciesKey), [speciesKey]);
  const allowedKeys = useMemo(() => new Set(genes.map((gene) => gene.key)), [genes]);
  const activeSpecies = useMemo(
    () => SPECIES.find((entry) => entry.key === speciesKey) ?? SPECIES[0],
    [speciesKey]
  );

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const speciesParam = params.get('s');
    const initialSpecies: SpeciesKey = speciesParam === 'pogona' ? 'pogona' : 'hognose';
    if (initialSpecies !== speciesKey) {
      setSpeciesKey(initialSpecies);
    }
    const initialGenes = getGenesForSpecies(initialSpecies);
    const initialSet = new Set(initialGenes.map((gene) => gene.key));
    const parsedA = parseParent(params.get('a'), initialSet);
    const parsedB = parseParent(params.get('b'), initialSet);
    if (Object.keys(parsedA).length > 0) {
      setParentA(parsedA);
    }
    if (Object.keys(parsedB).length > 0) {
      setParentB(parsedB);
    }
    setHydrated(true);
  }, []);

  useEffect(() => {
    if (!hydrated) {
      return;
    }
    const params = new URLSearchParams();
    params.set('s', speciesKey);
    const encodedA = encodeParent(parentA, allowedKeys);
    const encodedB = encodeParent(parentB, allowedKeys);
    if (encodedA) {
      params.set('a', encodedA);
    }
    if (encodedB) {
      params.set('b', encodedB);
    }
    const queryString = params.toString();
    const base = window.location.pathname;
    const nextUrl = queryString ? `${base}?${queryString}` : base;
    window.history.replaceState(null, '', nextUrl);
  }, [speciesKey, parentA, parentB, allowedKeys, hydrated]);

  useEffect(() => {
    if (!shareMessage) {
      return;
    }
    const timeout = window.setTimeout(() => setShareMessage(null), 2800);
    return () => window.clearTimeout(timeout);
  }, [shareMessage]);

  const handleSpeciesChange = (key: SpeciesKey) => {
    setSpeciesKey(key);
    setParentA({});
    setParentB({});
    setResults(null);
  };

  const handleCalculate = () => {
    const prediction = predictPairing(parentA, parentB, genes).slice(0, 50);
    setResults(prediction);
  };

  const handleReset = () => {
    setParentA({});
    setParentB({});
    setResults(null);
  };

  const activeResults = results ?? [];

  const handleShare = useCallback(async () => {
    const url = window.location.href;
    try {
      setSharePending(true);
      if (navigator.share) {
        await navigator.share({ title: 'Genetik-Rechner', url });
        setShareMessage('Link zum Rechner geteilt.');
        return;
      }
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(url);
        setShareMessage('Link in die Zwischenablage kopiert.');
        return;
      }
      const textarea = document.createElement('textarea');
      textarea.value = url;
      textarea.setAttribute('aria-hidden', 'true');
      textarea.style.position = 'fixed';
      textarea.style.top = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      try {
        const copied = document.execCommand('copy');
        setShareMessage(
          copied ? 'Link in die Zwischenablage kopiert.' : 'Teilen wird in diesem Browser nicht unterstützt.'
        );
      } finally {
        document.body.removeChild(textarea);
      }
    } catch (error) {
      console.error('Share failed', error);
      setShareMessage('Teilen wurde abgebrochen.');
    } finally {
      setSharePending(false);
    }
  }, []);

  return (
    <div className="genetics-calculator">
      <header className="nui-hero">
        <div className="nui-hero__shapes" aria-hidden="true" />
        <div className="nui-hero__content">
          <div className="nui-hero__meta">
            <div>
              <span className="nui-hero__eyebrow">MorphMarket Toolkit</span>
              <h1 className="nui-hero__title">Genetik-Rechner</h1>
              <p className="nui-hero__subtitle">
                {messages.calculate} · {messages.speciesHint}
              </p>
            </div>
            <div className="nui-hero__actions">
              <button
                type="button"
                className="nui-button nui-button--ghost"
                onClick={handleShare}
                disabled={sharePending}
              >
                <span className="nui-button__icon" aria-hidden="true">
                  <svg viewBox="0 0 20 20" focusable="false" role="presentation">
                    <path
                      d="M15 2a3 3 0 0 0-2.995 2.824L12 5a2.98 2.98 0 0 0 .053.552l-5.3 2.35a3 3 0 1 0-.453 4.095l5.4 2.394A2.99 2.99 0 0 0 12 15a3 3 0 1 0-.053-.552l-5.4-2.394a3 3 0 0 0 0-1.11l5.3-2.35A3 3 0 1 0 15 2Zm0 2a1 1 0 1 1-.001 1.999A1 1 0 0 1 15 4ZM5 9a1 1 0 1 1-.002 1.999A1 1 0 0 1 5 9Zm10 5a1 1 0 1 1-.001 1.999A1 1 0 0 1 15 14Z"
                      fill="currentColor"
                    />
                  </svg>
                </span>
                <span>{sharePending ? 'Wird geteilt…' : 'Teilen'}</span>
              </button>
            </div>
          </div>
          <dl className="nui-hero__badge" role="status">
            <div>
              <dt>Aktive Art</dt>
              <dd>{activeSpecies.label}</dd>
            </div>
            <div>
              <dt>Beschreibung</dt>
              <dd>{activeSpecies.subtitle}</dd>
            </div>
          </dl>
        </div>
      </header>
      <section className="genetics-calculator__species nui-card">
        <div className="nui-card__header">
          <div>
            <h2>{messages.speciesHeading}</h2>
            <p>{messages.speciesHint}</p>
          </div>
        </div>
        <div className="genetics-calculator__species-list" role="list">
          {SPECIES.map((entry) => (
            <button
              type="button"
              key={entry.key}
              className={clsx('chip-button', { 'is-active': entry.key === speciesKey })}
              onClick={() => handleSpeciesChange(entry.key)}
              aria-pressed={entry.key === speciesKey}
              role="listitem"
            >
              <span className="chip-button__label">{entry.label}</span>
              <span className="chip-button__subtitle">{entry.subtitle}</span>
            </button>
          ))}
        </div>
      </section>
      <section className="genetics-calculator__inputs">
        <div className="search-panel nui-card">
          <div className="panel-label-group">
            <div className="panel-label">{messages.parentA}</div>
            <div className="panel-subtitle">{activeSpecies.label}</div>
          </div>
          <GenotypeSearch species={speciesKey} value={parentA} onChange={setParentA} />
        </div>
        <div className="search-panel nui-card">
          <div className="panel-label-group">
            <div className="panel-label">{messages.parentB}</div>
            <div className="panel-subtitle">{activeSpecies.label}</div>
          </div>
          <GenotypeSearch species={speciesKey} value={parentB} onChange={setParentB} />
        </div>
      </section>
      <section className="genetics-calculator__actions nui-card nui-toolbar">
        <div className="nui-toolbar__info">
          <span className="nui-toolbar__eyebrow">Aktive Art</span>
          <span className="nui-toolbar__title">{activeSpecies.label}</span>
          <span className="nui-toolbar__subtitle">{activeSpecies.subtitle}</span>
        </div>
        <div className="nui-toolbar__buttons">
          <button type="button" className="action-secondary" onClick={handleReset}>
            {messages.reset}
          </button>
          <button type="button" className="action-primary" onClick={handleCalculate}>
            {messages.calculate}
          </button>
        </div>
      </section>
      <section className="genetics-calculator__results">
        <ResultTable
          results={activeResults}
          genes={genes}
          species={speciesKey}
          aliases={morphAliases as typeof morphAliases}
        />
      </section>
      {shareMessage && (
        <div className="nui-toast" role="status" aria-live="polite">
          {shareMessage}
        </div>
      )}
    </div>
  );
}
