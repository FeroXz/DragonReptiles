import { useMemo, useState } from 'react';
import clsx from 'clsx';
import { GeneDef, ParentGenotype, Zygosity } from '@lib/genetics/types.js';

interface ParentPickerProps {
  title: string;
  genes: GeneDef[];
  value: ParentGenotype;
  onChange: (next: ParentGenotype) => void;
  translations: ParentPickerTranslations;
}

export interface ParentPickerTranslations {
  normal: string;
  het: string;
  expressed: string;
  super: string;
  present: string;
  posHet: string;
  posHetHelper: string;
  warningIncompatible: string;
  sectionTitles: Record<'incomplete_dominant' | 'dominant' | 'recessive' | 'polygenic', string>;
  polygenicHint: string;
}

interface ParentEntry {
  state: Zygosity;
  posHet?: number;
}

const TYPE_ORDER: Array<GeneDef['type']> = [
  'incomplete_dominant',
  'dominant',
  'recessive',
  'polygenic'
];

function formatTemplate(template: string, params: Record<string, string>): string {
  return template.replace(/\{(\w+)}/g, (_, key) => params[key] ?? '');
}

function parseEntry(value: ParentGenotype[string] | undefined): ParentEntry {
  if (!value) {
    return { state: 'normal' };
  }
  if (typeof value === 'string') {
    return { state: value };
  }
  return { state: value.state, posHet: value.posHet };
}

function isSuperForm(gene: GeneDef, state: Zygosity): boolean {
  if (gene.type === 'recessive') {
    return state === 'expressed';
  }
  if (gene.type === 'incomplete_dominant') {
    return state === 'super';
  }
  return false;
}

function nextStateAfterClick(gene: GeneDef, target: Zygosity): Zygosity {
  if (gene.type === 'dominant' && target === 'super') {
    return 'expressed';
  }
  if (gene.type === 'incomplete_dominant' && target === 'het') {
    return 'expressed';
  }
  return target;
}

function SegmentedControl({
  gene,
  current,
  onSelect,
  translations
}: {
  gene: GeneDef;
  current: ParentEntry;
  onSelect: (state: Zygosity) => void;
  translations: ParentPickerTranslations;
}) {
  const options: Array<{ value: Zygosity; label: string } | null> = [];
  if (gene.type === 'recessive') {
    options.push({ value: 'normal', label: translations.normal });
    options.push({ value: 'het', label: translations.het });
    options.push({ value: 'expressed', label: translations.expressed });
  } else if (gene.type === 'incomplete_dominant') {
    options.push({ value: 'normal', label: translations.normal });
    options.push({ value: 'expressed', label: translations.expressed });
    options.push({ value: 'super', label: translations.super });
  } else if (gene.type === 'dominant') {
    options.push({ value: 'normal', label: translations.normal });
    options.push({ value: 'expressed', label: translations.present });
  } else {
    return null;
  }

  return (
    <div className="segmented-control" role="group" aria-label={gene.name}>
      {options.filter(Boolean).map((option) => {
        if (!option) return null;
        const isActive = current.state === option.value;
        return (
          <button
            key={option.value}
            type="button"
            className={clsx('segmented-control__option', { 'is-active': isActive })}
            aria-pressed={isActive}
            onClick={() => onSelect(nextStateAfterClick(gene, option.value))}
          >
            {option.label}
          </button>
        );
      })}
    </div>
  );
}

export function ParentPicker({ title, genes, value, onChange, translations }: ParentPickerProps) {
  const [warning, setWarning] = useState<string | null>(null);
  const geneMap = useMemo(() => new Map(genes.map((gene) => [gene.key, gene])), [genes]);
  const groups = useMemo(() => {
    const entries: Array<{ type: GeneDef['type']; genes: GeneDef[] }> = [];
    TYPE_ORDER.forEach((type) => {
      const list = genes.filter((gene) => gene.type === type && gene.visible !== false);
      if (list.length) {
        entries.push({ type, genes: list });
      }
    });
    return entries;
  }, [genes]);

  const updateGene = (gene: GeneDef, nextState: Zygosity, posHet?: number) => {
    const next: ParentGenotype = { ...value };
    if (gene.type === 'polygenic') {
      if (nextState === 'expressed') {
        next[gene.key] = 'expressed';
      } else {
        delete next[gene.key];
      }
      onChange(next);
      return;
    }
    if (nextState === 'normal' && (posHet === undefined || posHet <= 0)) {
      delete next[gene.key];
    } else if (gene.type === 'recessive' && posHet !== undefined && posHet > 0) {
      next[gene.key] = { state: nextState, posHet };
    } else {
      next[gene.key] = nextState;
    }
    onChange(next);
  };

  const handleStateChange = (gene: GeneDef, state: Zygosity) => {
    const entry = parseEntry(value[gene.key]);
    if (entry.state === state && !(gene.type === 'recessive' && state === 'normal' && entry.posHet)) {
      return;
    }

    if (isSuperForm(gene, state) && Array.isArray(gene.incompatibleWith) && gene.incompatibleWith.length) {
      const conflict = gene.incompatibleWith
        .map((key) => geneMap.get(key))
        .filter(Boolean)
        .find((otherGene) => {
          if (!otherGene) {
            return false;
          }
          const otherEntry = parseEntry(value[otherGene.key]);
          return isSuperForm(otherGene, otherEntry.state);
        });
      if (conflict) {
        setWarning(formatTemplate(translations.warningIncompatible, { gene: gene.name, conflict: conflict.name }));
        return;
      }
    }

    setWarning(null);
    if (gene.type === 'recessive' && entry.posHet !== undefined) {
      updateGene(gene, state, entry.posHet);
    } else {
      updateGene(gene, state);
    }
  };

  const handlePolygenicToggle = (gene: GeneDef, checked: boolean) => {
    updateGene(gene, checked ? 'expressed' : 'normal');
  };

  const handlePosHetToggle = (gene: GeneDef, enabled: boolean) => {
    const entry = parseEntry(value[gene.key]);
    const baseState = entry.state ?? 'normal';
    if (!enabled) {
      if (baseState === 'normal') {
        updateGene(gene, 'normal');
      } else {
        updateGene(gene, baseState);
      }
      return;
    }
    const initial = entry.posHet ?? 50;
    updateGene(gene, baseState, initial);
  };

  const handlePosHetChange = (gene: GeneDef, percent: number) => {
    const entry = parseEntry(value[gene.key]);
    const nextState = entry.state ?? 'normal';
    const nextPercent = Math.max(0, Math.min(100, Math.round(percent)));
    if (nextPercent <= 0) {
      if (nextState === 'normal') {
        updateGene(gene, 'normal');
      } else {
        updateGene(gene, nextState);
      }
      return;
    }
    updateGene(gene, nextState, nextPercent);
  };

  return (
    <section className="parent-picker">
      <header className="parent-picker__header">
        <h2>{title}</h2>
        {warning && <p className="parent-picker__warning" role="alert">{warning}</p>}
      </header>
      <div className="parent-picker__sections">
        {groups.map((group) => (
          <div className="parent-picker__section" key={group.type}>
            <h3 className="parent-picker__section-title">{translations.sectionTitles[group.type]}</h3>
            <div className="parent-picker__genes">
              {group.genes.map((gene) => {
                const entry = parseEntry(value[gene.key]);
                const posHetActive = gene.type === 'recessive' && entry.posHet !== undefined;
                return (
                  <article className="parent-picker__gene" key={gene.key}>
                    <div className="parent-picker__gene-header">
                      <div>
                        <span className="parent-picker__gene-name">{gene.name}</span>
                        {gene.notes && <span className="parent-picker__gene-notes">{gene.notes}</span>}
                      </div>
                    </div>
                    {gene.type === 'polygenic' ? (
                      <label className="parent-picker__polygenic">
                        <input
                          type="checkbox"
                          checked={entry.state === 'expressed'}
                          onChange={(event) => handlePolygenicToggle(gene, event.target.checked)}
                        />
                        <span>{translations.polygenicHint}</span>
                      </label>
                    ) : (
                      <>
                        <SegmentedControl
                          gene={gene}
                          current={entry}
                          onSelect={(state) => handleStateChange(gene, state)}
                          translations={translations}
                        />
                        {gene.type === 'recessive' && (
                          <div className="parent-picker__poshet">
                            <label className="parent-picker__poshet-toggle">
                              <input
                                type="checkbox"
                                checked={posHetActive}
                                onChange={(event) => handlePosHetToggle(gene, event.target.checked)}
                              />
                              <span>{translations.posHet}</span>
                            </label>
                            {posHetActive && (
                              <div className="parent-picker__poshet-slider">
                                <input
                                  type="range"
                                  min={0}
                                  max={100}
                                  step={1}
                                  value={entry.posHet ?? 0}
                                  onChange={(event) => handlePosHetChange(gene, Number(event.target.value))}
                                />
                                <span className="parent-picker__poshet-value">{entry.posHet ?? 0}%</span>
                                <span className="parent-picker__poshet-hint">{translations.posHetHelper}</span>
                              </div>
                            )}
                          </div>
                        )}
                      </>
                    )}
                  </article>
                );
              })}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
