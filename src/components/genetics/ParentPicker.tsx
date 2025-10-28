import { useMemo, useState } from 'react';
import clsx from 'clsx';
import { GeneDef, ParentGenotype, Zygosity } from '@lib/genetics/types.js';

interface Messages {
  normal: string;
  het: string;
  expressed: string;
  super: string;
  present: string;
  posHet: string;
  polygenicHint: string;
  warningIncompatible: string;
  sectionTitles: Record<'recessive' | 'incomplete_dominant' | 'dominant' | 'polygenic', string>;
}

interface ParentPickerProps {
  label: string;
  genes: GeneDef[];
  value: ParentGenotype;
  onChange: (value: ParentGenotype) => void;
  messages: Messages;
}

type GeneGroup = 'incomplete_dominant' | 'dominant' | 'recessive' | 'polygenic';

type NormalizedEntry = { state: Zygosity; posHet?: number };

const GROUP_ORDER: GeneGroup[] = ['incomplete_dominant', 'dominant', 'recessive', 'polygenic'];

function normalizeEntry(entry: ParentGenotype[string]): NormalizedEntry {
  if (!entry) {
    return { state: 'normal' };
  }
  if (typeof entry === 'string') {
    return { state: entry };
  }
  return { state: entry.state, posHet: entry.posHet };
}

function buildWarning(message: string, gene: GeneDef, conflict: GeneDef | undefined): string {
  return message
    .replace('{gene}', gene.name)
    .replace('{conflict}', conflict?.name ?? '');
}

export function ParentPicker({ label, genes, value, onChange, messages }: ParentPickerProps) {
  const [warnings, setWarnings] = useState<Record<string, string | null>>({});

  const genesByGroup = useMemo(() => {
    const grouped = new Map<GeneGroup, GeneDef[]>();
    GROUP_ORDER.forEach((group) => {
      grouped.set(group, []);
    });
    genes.forEach((gene) => {
      if (gene.type === 'polygenic' && gene.visible === false) {
        return;
      }
      const bucket = grouped.get(gene.type as GeneGroup);
      if (bucket) {
        bucket.push(gene);
      }
    });
    GROUP_ORDER.forEach((group) => {
      const bucket = grouped.get(group);
      if (bucket) {
        bucket.sort((a, b) => a.name.localeCompare(b.name, 'de'));
      }
    });
    return grouped;
  }, [genes]);

  const resolveGene = (key: string) => genes.find((item) => item.key === key);

  const applyChange = (gene: GeneDef, next: NormalizedEntry) => {
    if (gene.incompatibleWith && next.state === 'super') {
      const conflictKey = gene.incompatibleWith.find((candidate) => {
        const entry = normalizeEntry(value[candidate]);
        return entry.state === 'super';
      });
      if (conflictKey) {
        const conflictGene = resolveGene(conflictKey);
        setWarnings((prev) => ({ ...prev, [gene.key]: buildWarning(messages.warningIncompatible, gene, conflictGene) }));
        return;
      }
    }
    setWarnings((prev) => ({ ...prev, [gene.key]: null }));

    const nextValue: ParentGenotype = { ...value };
    if (next.state === 'normal' && next.posHet === undefined) {
      delete nextValue[gene.key];
    } else if (next.posHet !== undefined) {
      nextValue[gene.key] = { state: next.state, posHet: next.posHet };
    } else {
      nextValue[gene.key] = next.state;
    }
    onChange(nextValue);
  };

  const handleSegmentChange = (gene: GeneDef, state: Zygosity) => {
    const entry = normalizeEntry(value[gene.key]);
    const next: NormalizedEntry = { state };
    if (state === 'het' && entry.posHet !== undefined) {
      next.posHet = entry.posHet;
    }
    applyChange(gene, next);
  };

  const togglePosHet = (gene: GeneDef, enabled: boolean) => {
    const entry = normalizeEntry(value[gene.key]);
    const base: NormalizedEntry = { state: 'het' };
    if (enabled) {
      base.posHet = entry.posHet ?? 50;
    }
    applyChange(gene, base);
  };

  const updatePosHetValue = (gene: GeneDef, valuePct: number) => {
    applyChange(gene, { state: 'het', posHet: valuePct });
  };

  const togglePolygenic = (gene: GeneDef, checked: boolean) => {
    applyChange(gene, { state: checked ? 'expressed' : 'normal' });
  };

  const renderRecessive = (gene: GeneDef) => {
    const entry = normalizeEntry(value[gene.key]);
    const isHet = entry.state === 'het';
    const posHetEnabled = isHet && entry.posHet !== undefined;
    const posHetValue = posHetEnabled ? entry.posHet ?? 50 : 50;

    return (
      <div className="gene-card" key={gene.key}>
        <div className="gene-card__header">
          <span className="gene-card__title">{gene.name}</span>
          <span className="gene-card__type">{messages.sectionTitles.recessive}</span>
        </div>
        <div className="segmented-control" role="radiogroup" aria-label={gene.name}>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'normal' })}
            onClick={() => handleSegmentChange(gene, 'normal')}
          >
            {messages.normal}
          </button>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'het' })}
            onClick={() => handleSegmentChange(gene, 'het')}
          >
            {messages.het}
          </button>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'expressed' })}
            onClick={() => handleSegmentChange(gene, 'expressed')}
          >
            {messages.expressed}
          </button>
        </div>
        {isHet && (
          <div className="poshet-control">
            <label className="poshet-control__toggle">
              <input
                type="checkbox"
                checked={posHetEnabled}
                onChange={(event) => togglePosHet(gene, event.target.checked)}
              />
              <span>{messages.posHet}</span>
            </label>
            {posHetEnabled && (
              <input
                type="range"
                min={5}
                max={95}
                step={1}
                value={posHetValue}
                className="poshet-control__slider"
                onChange={(event) => updatePosHetValue(gene, Number(event.target.value))}
              />
            )}
            {posHetEnabled && <span className="poshet-control__value">{posHetValue}%</span>}
          </div>
        )}
        {warnings[gene.key] && <p className="gene-card__warning">{warnings[gene.key]}</p>}
      </div>
    );
  };

  const renderIncompleteDominant = (gene: GeneDef) => {
    const entry = normalizeEntry(value[gene.key]);
    return (
      <div className="gene-card" key={gene.key}>
        <div className="gene-card__header">
          <span className="gene-card__title">{gene.name}</span>
          <span className="gene-card__type">{messages.sectionTitles.incomplete_dominant}</span>
        </div>
        <div className="segmented-control" role="radiogroup" aria-label={gene.name}>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'normal' })}
            onClick={() => handleSegmentChange(gene, 'normal')}
          >
            {messages.normal}
          </button>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'expressed' })}
            onClick={() => handleSegmentChange(gene, 'expressed')}
          >
            {messages.expressed}
          </button>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'super' })}
            onClick={() => handleSegmentChange(gene, 'super')}
          >
            {messages.super}
          </button>
        </div>
        {warnings[gene.key] && <p className="gene-card__warning">{warnings[gene.key]}</p>}
      </div>
    );
  };

  const renderDominant = (gene: GeneDef) => {
    const entry = normalizeEntry(value[gene.key]);
    return (
      <div className="gene-card" key={gene.key}>
        <div className="gene-card__header">
          <span className="gene-card__title">{gene.name}</span>
          <span className="gene-card__type">{messages.sectionTitles.dominant}</span>
        </div>
        <div className="segmented-control" role="radiogroup" aria-label={gene.name}>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'normal' })}
            onClick={() => handleSegmentChange(gene, 'normal')}
          >
            {messages.normal}
          </button>
          <button
            type="button"
            className={clsx('segmented-control__option', { 'is-active': entry.state === 'expressed' })}
            onClick={() => handleSegmentChange(gene, 'expressed')}
          >
            {messages.present}
          </button>
        </div>
      </div>
    );
  };

  const renderPolygenic = (gene: GeneDef) => {
    const entry = normalizeEntry(value[gene.key]);
    const checked = entry.state === 'expressed';
    return (
      <div className="gene-card" key={gene.key}>
        <div className="gene-card__header">
          <span className="gene-card__title">{gene.name}</span>
          <span className="gene-card__type">{messages.sectionTitles.polygenic}</span>
        </div>
        <label className="polygenic-control">
          <input
            type="checkbox"
            checked={checked}
            onChange={(event) => togglePolygenic(gene, event.target.checked)}
          />
          <span>{messages.polygenicHint}</span>
        </label>
      </div>
    );
  };

  const renderGroup = (group: GeneGroup) => {
    const list = genesByGroup.get(group);
    if (!list || !list.length) {
      return null;
    }
    const renderers: Record<GeneGroup, (gene: GeneDef) => JSX.Element> = {
      recessive: renderRecessive,
      incomplete_dominant: renderIncompleteDominant,
      dominant: renderDominant,
      polygenic: renderPolygenic
    };
    return (
      <div className="parent-picker__section" key={group}>
        <div className="parent-picker__genes">
          {list.map((gene) => renderers[group](gene))}
        </div>
      </div>
    );
  };

  return (
    <section className="parent-picker" aria-label={label}>
      <header className="parent-picker__header">
        <h3 className="parent-picker__title">{label}</h3>
      </header>
      <div className="parent-picker__content">
        {GROUP_ORDER.map((group) => renderGroup(group))}
      </div>
    </section>
  );
}
