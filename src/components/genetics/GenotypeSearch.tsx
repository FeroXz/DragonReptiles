import clsx from 'clsx';
import { useCallback, useEffect, useId, useMemo, useRef, useState, type KeyboardEventHandler } from 'react';
import { buildOptions, Option, OptionGroup } from '@lib/genetics/options.js';
import { getGenesForSpecies } from '@lib/genetics/species.js';
import { getSearchPresets, SearchPreset } from '@lib/genetics/presets.js';
import { GeneDef, ParentGenotype, SpeciesKey, Zygosity } from '@lib/genetics/types.js';

interface GenotypeSearchProps {
  species: SpeciesKey;
  value: ParentGenotype;
  onChange: (next: ParentGenotype) => void;
  presets?: SearchPreset[];
}

type SearchOptionGroup = OptionGroup | 'preset';

const GROUP_LABELS: Record<SearchOptionGroup, string> = {
  id: 'Inkomplett Dominant',
  recessive: 'Rezessiv',
  dominant: 'Dominant',
  poly: 'Polygen',
  preset: 'Morph-Kombinationen'
};

const GROUP_ORDER: SearchOptionGroup[] = ['preset', 'id', 'recessive', 'dominant', 'poly'];

type GeneOptionEntry = { kind: 'gene'; option: Option };
type PresetOptionEntry = {
  kind: 'preset';
  label: string;
  keywords: string[];
  preset: SearchPreset;
};

type SearchOption = GeneOptionEntry | PresetOptionEntry;

function normalizeState(entry: ParentGenotype[string]): Zygosity {
  if (!entry) {
    return 'normal';
  }
  if (typeof entry === 'string') {
    return entry;
  }
  return entry.state;
}

function mapStateForOption(gene: GeneDef, state: Zygosity): Option['state'] | null {
  if (state === 'normal') {
    return null;
  }
  if (gene.type === 'dominant') {
    return 'present';
  }
  if (gene.type === 'polygenic') {
    return 'poly';
  }
  if (state === 'het') {
    return 'het';
  }
  if (state === 'super') {
    return 'super';
  }
  return 'expressed';
}

function optionStateToZygosity(option: Option, gene: GeneDef): Zygosity {
  switch (option.state) {
    case 'het':
      return 'het';
    case 'super':
      return 'super';
    case 'present':
      return 'expressed';
    case 'poly':
      return 'expressed';
    case 'expressed':
    default:
      if (gene.type === 'dominant') {
        return 'expressed';
      }
      return 'expressed';
  }
}

function useDebouncedValue<T>(value: T, delay = 160): T {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const timeout = window.setTimeout(() => setDebounced(value), delay);
    return () => window.clearTimeout(timeout);
  }, [value, delay]);
  return debounced;
}

function hasActiveState(entry: ParentGenotype[string]): boolean {
  if (!entry) {
    return false;
  }
  if (typeof entry === 'string') {
    return entry !== 'normal';
  }
  return entry.state !== 'normal' || entry.posHet !== undefined;
}

function normalizePercent(value: number | undefined): number | undefined {
  if (value === undefined) {
    return undefined;
  }
  const canonical = [33, 50, 66];
  const rounded = Math.round(value);
  for (const target of canonical) {
    if (Math.abs(rounded - target) <= 3) {
      return target;
    }
  }
  return rounded;
}

const TYPE_LABELS: Record<GeneDef['type'], string> = {
  recessive: 'Rezessiv',
  incomplete_dominant: 'Inkomplett dominant',
  dominant: 'Dominant',
  polygenic: 'Polygen'
};

type TraitAccent = 'rec' | 'id' | 'dom' | 'poly';

function geneTypeLabel(gene: GeneDef): string {
  return TYPE_LABELS[gene.type] ?? 'Gen';
}

function accentForGene(gene: GeneDef): TraitAccent {
  switch (gene.type) {
    case 'recessive':
      return 'rec';
    case 'dominant':
      return 'dom';
    case 'polygenic':
      return 'poly';
    case 'incomplete_dominant':
    default:
      return 'id';
  }
}

function selectionStateLabel(gene: GeneDef, state: Zygosity, posHet?: number): string {
  if (gene.type === 'recessive') {
    if (state === 'het') {
      const percent = normalizePercent(posHet);
      return percent !== undefined ? `Träger · ${percent}%` : 'Träger (het)';
    }
    if (state === 'expressed') {
      return 'Visuell';
    }
  }
  if (gene.type === 'incomplete_dominant') {
    if (state === 'super') {
      return 'Superform';
    }
    if (state === 'expressed') {
      return 'Ausgeprägt';
    }
  }
  if (gene.type === 'dominant') {
    return 'Dominant';
  }
  if (gene.type === 'polygenic') {
    return 'Linie aktiv';
  }
  if (state === 'super') {
    return 'Superform';
  }
  if (state === 'het') {
    return 'Träger (het)';
  }
  return 'Aktiv';
}

interface SelectedChip {
  gene: GeneDef;
  option: Option;
  label: string;
  typeLabel: string;
  stateLabel: string;
  accent: TraitAccent;
  initial: string;
}

export function GenotypeSearch({ species, value, onChange, presets }: GenotypeSearchProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const searchInputId = useId();
  const genes = useMemo(() => getGenesForSpecies(species), [species]);
  const geneOptions = useMemo(() => buildOptions(genes), [genes]);
  const presetList = useMemo(() => presets ?? getSearchPresets(species), [presets, species]);
  const [query, setQuery] = useState('');
  const debouncedQuery = useDebouncedValue(query.trim().toLowerCase());
  const [open, setOpen] = useState(false);
  const [highlightIndex, setHighlightIndex] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const geneMap = useMemo(() => {
    const map = new Map<string, GeneDef>();
    genes.forEach((gene) => map.set(gene.key, gene));
    return map;
  }, [genes]);

  const geneOptionEntries = useMemo<GeneOptionEntry[]>(
    () => geneOptions.map((option) => ({ kind: 'gene', option })),
    [geneOptions]
  );

  const presetOptions = useMemo<PresetOptionEntry[]>(() => {
    if (!presetList.length) {
      return [];
    }
    const geneMap = new Map<string, GeneDef>();
    genes.forEach((gene) => geneMap.set(gene.key, gene));

    return presetList
      .map((preset) => {
        if (!preset.genes.every((entry) => geneMap.has(entry.key))) {
          return null;
        }
        const keywords = new Set<string>();
        const addTokens = (value: string) => {
          value
            .split(/[\s/-]+/)
            .map((token) => token.trim().toLowerCase())
            .filter(Boolean)
            .forEach((token) => keywords.add(token));
        };
        addTokens(preset.label);
        (preset.keywords ?? []).forEach(addTokens);
        preset.genes.forEach((entry) => {
          const gene = geneMap.get(entry.key);
          if (!gene) {
            return;
          }
          addTokens(gene.name);
          (gene.aliases ?? []).forEach(addTokens);
          (gene.searchAliases ?? []).forEach(addTokens);
        });

        return {
          kind: 'preset' as const,
          label: preset.label,
          keywords: Array.from(keywords),
          preset
        };
      })
      .filter((entry): entry is PresetOptionEntry => Boolean(entry));
  }, [genes, presetList]);

  const allOptions = useMemo<SearchOption[]>(() => [...presetOptions, ...geneOptionEntries], [presetOptions, geneOptionEntries]);

  const optionMap = useMemo(() => {
    const map = new Map<string, Map<Option['state'], Option>>();
    geneOptionEntries.forEach(({ option }) => {
      let entry = map.get(option.geneKey);
      if (!entry) {
        entry = new Map();
        map.set(option.geneKey, entry);
      }
      entry.set(option.state, option);
    });
    return map;
  }, [geneOptionEntries]);

  const filtered = useMemo(() => {
    if (!debouncedQuery) {
      return allOptions;
    }
    return allOptions.filter((entry) => {
      if (entry.kind === 'gene') {
        return entry.option.keywords.some((keyword) => keyword.includes(debouncedQuery));
      }
      return entry.keywords.some((keyword) => keyword.includes(debouncedQuery));
    });
  }, [allOptions, debouncedQuery]);

  const grouped = useMemo(() => {
    return GROUP_ORDER.map((group) => {
      const optionsForGroup = filtered.filter((entry) => {
        if (entry.kind === 'gene') {
          return entry.option.group === group;
        }
        return group === 'preset';
      });
      return {
        group,
        label: GROUP_LABELS[group],
        options: optionsForGroup
      };
    }).filter((entry) => entry.options.length > 0);
  }, [filtered]);

  const flattened = useMemo(() => grouped.flatMap((entry) => entry.options), [grouped]);
  const indexMap = useMemo(() => {
    const map = new Map<SearchOption, number>();
    flattened.forEach((option, index) => {
      map.set(option, index);
    });
    return map;
  }, [flattened]);

  const checkConflicts = useCallback(
    (next: ParentGenotype): string | null => {
      for (const gene of genes) {
        if (!hasActiveState(next[gene.key])) {
          continue;
        }
        const conflicts = gene.incompatibleWith ?? [];
        for (const conflictKey of conflicts) {
          if (!hasActiveState(next[conflictKey])) {
            continue;
          }
          const conflictGene = geneMap.get(conflictKey);
          const conflictName = conflictGene?.name ?? conflictKey;
          return `${gene.name} kollidiert mit ${conflictName}`;
        }
      }
      return null;
    },
    [genes, geneMap]
  );

  useEffect(() => {
    if (!error) {
      return;
    }
    if (!checkConflicts(value)) {
      setError(null);
    }
  }, [value, error, checkConflicts]);

  useEffect(() => {
    setHighlightIndex(0);
  }, [debouncedQuery, grouped.length]);

  useEffect(() => {
    const handleClick = (event: MouseEvent) => {
      if (!containerRef.current) {
        return;
      }
      if (!containerRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  const selectedChips = useMemo<SelectedChip[]>(() => {
    return genes
      .map((gene) => {
        const rawEntry = value[gene.key];
        const state = normalizeState(rawEntry);
        if (state === 'normal') {
          return null;
        }
        const optionState = mapStateForOption(gene, state);
        if (!optionState) {
          return null;
        }
        const option = optionMap.get(gene.key)?.get(optionState);
        if (!option) {
          return null;
        }
        const posHet = typeof rawEntry === 'object' ? rawEntry.posHet : undefined;
        return {
          gene,
          option,
          label: option.label,
          typeLabel: geneTypeLabel(gene),
          stateLabel: selectionStateLabel(gene, state, posHet),
          accent: accentForGene(gene),
          initial: option.label.charAt(0).toUpperCase()
        } satisfies SelectedChip;
      })
      .filter((entry): entry is SelectedChip => Boolean(entry));
  }, [genes, optionMap, value]);

  const handleSelect = (entry: SearchOption) => {
    if (entry.kind === 'preset') {
      const next: ParentGenotype = { ...value };
      entry.preset.genes.forEach((geneEntry) => {
        const gene = genes.find((item) => item.key === geneEntry.key);
        if (!gene) {
          return;
        }
        if (geneEntry.posHet !== undefined) {
          next[geneEntry.key] = { state: geneEntry.state, posHet: geneEntry.posHet };
        } else {
          next[geneEntry.key] = geneEntry.state;
        }
      });
      const conflict = checkConflicts(next);
      if (conflict) {
        setError(conflict);
        return;
      }
      setError(null);
      setQuery('');
      setOpen(true);
      setHighlightIndex(0);
      onChange(next);
      window.requestAnimationFrame(() => {
        inputRef.current?.focus();
      });
      return;
    }
    const option = entry.option;
    const gene = genes.find((item) => item.key === option.geneKey);
    if (!gene) {
      return;
    }
    const next: ParentGenotype = { ...value };
    const state = optionStateToZygosity(option, gene);
    const current = normalizeState(next[option.geneKey]);
    if (current === state) {
      delete next[option.geneKey];
    } else {
      next[option.geneKey] = state;
    }
    const conflict = checkConflicts(next);
    if (conflict) {
      setError(conflict);
      return;
    }
    setError(null);
    setQuery('');
    setOpen(true);
    setHighlightIndex(0);
    onChange(next);
    window.requestAnimationFrame(() => {
      inputRef.current?.focus();
    });
  };

  const handleRemove = (geneKey: string) => {
    const next: ParentGenotype = { ...value };
    delete next[geneKey];
    onChange(next);
    setError(null);
    setOpen(true);
    window.requestAnimationFrame(() => {
      inputRef.current?.focus();
    });
  };

  const handleInputFocus = () => {
    setOpen(true);
  };

  const handleKeyDown: KeyboardEventHandler<HTMLInputElement> = (event) => {
    if (!flattened.length) {
      if (event.key === 'Escape') {
        setOpen(false);
      }
      return;
    }
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setOpen(true);
      setHighlightIndex((prev) => (prev + 1) % flattened.length);
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      setOpen(true);
      setHighlightIndex((prev) => (prev - 1 + flattened.length) % flattened.length);
    } else if (event.key === 'Enter') {
      event.preventDefault();
      const option = flattened[highlightIndex];
      if (option) {
        handleSelect(option);
      }
    } else if (event.key === 'Escape') {
      setOpen(false);
    }
  };

  useEffect(() => {
    if (!flattened.length) {
      setHighlightIndex(0);
    } else if (highlightIndex >= flattened.length) {
      setHighlightIndex(0);
    }
  }, [flattened, highlightIndex]);

  return (
    <div className="genotype-search" ref={containerRef}>
      <div className="nui-chip-tray" aria-live="polite">
        {selectedChips.length === 0 ? (
          <div className="nui-chip-placeholder">Keine Traits ausgewählt</div>
        ) : (
          <ul className="trait-pill-grid" role="list">
            {selectedChips.map((chip) => (
              <li key={`${chip.gene.key}-${chip.option.state}`}>
                <button
                  type="button"
                  className={clsx('trait-pill', `trait-pill--${chip.accent}`)}
                  onClick={() => handleRemove(chip.gene.key)}
                  aria-label={`Trait ${chip.label} entfernen`}
                >
                  <span className="trait-pill__icon" aria-hidden="true">
                    {chip.initial}
                  </span>
                  <span className="trait-pill__body">
                    <span className="trait-pill__name" aria-hidden="true">
                      {chip.label}
                    </span>
                    <span className="trait-pill__meta" aria-hidden="true">
                      {chip.typeLabel}
                    </span>
                    <span className="trait-pill__state" aria-hidden="true">
                      {chip.stateLabel}
                    </span>
                  </span>
                  <span className="trait-pill__remove" aria-hidden="true">×</span>
                  <span className="sr-only">Trait {chip.label} entfernen</span>
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
      <div className="nui-field">
        <label className="nui-field__label" htmlFor={searchInputId}>
          Traits durchsuchen
        </label>
        <div className={query ? 'nui-input-wrap has-value' : 'nui-input-wrap'}>
          <span className="nui-input-icon" aria-hidden="true">
            <svg viewBox="0 0 20 20" focusable="false" role="presentation">
              <path
                d="M8.5 2a6.5 6.5 0 0 1 5.195 10.406l3.449 3.45a1 1 0 0 1-1.414 1.414l-3.45-3.449A6.5 6.5 0 1 1 8.5 2Zm0 2a4.5 4.5 0 1 0 0 9a4.5 4.5 0 0 0 0-9Z"
                fill="currentColor"
              />
            </svg>
          </span>
          <input
            id={searchInputId}
            ref={inputRef}
            className="nui-input"
            type="text"
            value={query}
            onChange={(event) => {
              setQuery(event.target.value);
              if (!open) {
                setOpen(true);
              }
              if (error) {
                setError(null);
              }
            }}
            onFocus={handleInputFocus}
            onKeyDown={handleKeyDown}
            placeholder="Suche nach Traits"
            aria-expanded={open}
            aria-controls={`${searchInputId}-dropdown`}
            role="combobox"
            aria-autocomplete="list"
          />
          {query && (
            <button
              type="button"
              className="nui-input-clear"
              onClick={() => {
                setQuery('');
                inputRef.current?.focus();
              }}
              aria-label="Suche leeren"
            >
              <svg viewBox="0 0 20 20" focusable="false" role="presentation">
                <path
                  d="m5.707 5.707 9 9a1 1 0 0 1-1.414 1.414l-9-9A1 1 0 0 1 5.707 5.707Zm9 0a1 1 0 0 1 0 1.414l-9 9A1 1 0 0 1 4.293 14.293l9-9a1 1 0 0 1 1.414 0Z"
                  fill="currentColor"
                />
              </svg>
            </button>
          )}
        </div>
        <p className="nui-field__hint">Mehrfachauswahl möglich – ausgewählte Traits bleiben markiert.</p>
      </div>
      {error && <div className="nui-alert" role="alert">{error}</div>}
      {open && (
        <div
          className="nui-dropdown"
          id={`${searchInputId}-dropdown`}
          role="listbox"
          aria-multiselectable="true"
        >
          {grouped.length === 0 && <div className="nui-dropdown__empty">Keine Treffer</div>}
          {grouped.map((entry) => (
            <div key={entry.group} className="nui-dropdown__group">
              <div className="nui-dropdown__group-label">{GROUP_LABELS[entry.group]}</div>
              <ul>
                {entry.options.map((option) => {
                  const index = indexMap.get(option) ?? 0;
                  const isActive = index === highlightIndex;
                  const key = option.kind === 'gene'
                    ? `${option.option.geneKey}-${option.option.state}`
                    : `preset-${option.label}`;
                  const label = option.kind === 'gene' ? option.option.label : option.label;
                  const accessibleLabel = label;
                  const gene = option.kind === 'gene' ? geneMap.get(option.option.geneKey) : null;
                  const expectedState = option.kind === 'gene' && gene
                    ? optionStateToZygosity(option.option, gene)
                    : null;
                  const currentState = option.kind === 'gene'
                    ? normalizeState(value[option.option.geneKey])
                    : null;
                  const isSelected = option.kind === 'gene' && expectedState === currentState;
                  const metaLabel = option.kind === 'gene' && gene
                    ? gene.type === 'dominant'
                      ? 'Dominant'
                      : gene.type === 'recessive'
                        ? 'Rezessiv'
                        : gene.type === 'incomplete_dominant'
                          ? 'Inkomplett dominant'
                          : gene.type === 'polygenic'
                            ? 'Polygen'
                            : null
                    : option.kind === 'preset'
                      ? 'Kombination'
                      : null;
                  const ariaSelected = option.kind === 'gene' ? isSelected : isActive;
                  return (
                    <li key={key}>
                      <button
                        type="button"
                        className={clsx('nui-option', {
                          'is-active': isActive,
                          'is-selected': isSelected
                        })}
                        aria-label={accessibleLabel}
                        onMouseEnter={() => setHighlightIndex(index)}
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => handleSelect(option)}
                        role="option"
                        aria-selected={ariaSelected}
                      >
                        <span className="nui-option__label">{label}</span>
                        {metaLabel && (
                          <span className="nui-option__meta" aria-hidden="true">
                            {metaLabel}
                          </span>
                        )}
                        {isSelected && (
                          <span className="nui-option__check" aria-hidden="true">
                            <svg viewBox="0 0 20 20" focusable="false" role="presentation">
                              <path
                                d="M16.707 6.293a1 1 0 0 1 0 1.414l-6.364 6.364a1 1 0 0 1-1.414 0l-3.536-3.536a1 1 0 0 1 1.414-1.414L9.64 11.64l5.657-5.647a1 1 0 0 1 1.414 0Z"
                                fill="currentColor"
                              />
                            </svg>
                          </span>
                        )}
                      </button>
                    </li>
                  );
                })}
              </ul>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
