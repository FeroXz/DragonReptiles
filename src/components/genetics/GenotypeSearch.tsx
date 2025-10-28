import { useEffect, useMemo, useRef, useState, type KeyboardEventHandler } from 'react';
import { buildOptions, Option, OptionGroup } from '@lib/genetics/options.js';
import { getGenesForSpecies } from '@lib/genetics/species.js';
import { GeneDef, ParentGenotype, SpeciesKey, Zygosity } from '@lib/genetics/types.js';

interface GenotypeSearchProps {
  species: SpeciesKey;
  value: ParentGenotype;
  onChange: (next: ParentGenotype) => void;
}

const GROUP_LABELS: Record<OptionGroup, string> = {
  id: 'Inkomplett Dominant',
  recessive: 'Rezessiv',
  dominant: 'Dominant',
  poly: 'Polygen'
};

const GROUP_ORDER: OptionGroup[] = ['id', 'recessive', 'dominant', 'poly'];

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

export function GenotypeSearch({ species, value, onChange }: GenotypeSearchProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const genes = useMemo(() => getGenesForSpecies(species), [species]);
  const options = useMemo(() => buildOptions(genes), [genes]);
  const [query, setQuery] = useState('');
  const debouncedQuery = useDebouncedValue(query.trim().toLowerCase());
  const [open, setOpen] = useState(false);
  const [highlightIndex, setHighlightIndex] = useState(0);

  const optionMap = useMemo(() => {
    const map = new Map<string, Map<Option['state'], Option>>();
    options.forEach((option) => {
      let entry = map.get(option.geneKey);
      if (!entry) {
        entry = new Map();
        map.set(option.geneKey, entry);
      }
      entry.set(option.state, option);
    });
    return map;
  }, [options]);

  const filtered = useMemo(() => {
    if (!debouncedQuery) {
      return options;
    }
    return options.filter((option) => option.keywords.some((keyword) => keyword.includes(debouncedQuery)));
  }, [options, debouncedQuery]);

  const grouped = useMemo(() => {
    const list = GROUP_ORDER.map((group) => ({
      group,
      label: GROUP_LABELS[group],
      options: filtered.filter((option) => option.group === group)
    })).filter((entry) => entry.options.length > 0);
    return list;
  }, [filtered]);

  const flattened = useMemo(() => grouped.flatMap((entry) => entry.options), [grouped]);
  const indexMap = useMemo(() => {
    const map = new Map<Option, number>();
    flattened.forEach((option, index) => {
      map.set(option, index);
    });
    return map;
  }, [flattened]);

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

  const selectedChips = useMemo(() => {
    return genes
      .map((gene) => {
        const state = normalizeState(value[gene.key]);
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
        return {
          gene,
          option
        };
      })
      .filter((entry): entry is { gene: GeneDef; option: Option } => Boolean(entry));
  }, [genes, optionMap, value]);

  const handleSelect = (option: Option) => {
    const gene = genes.find((item) => item.key === option.geneKey);
    if (!gene) {
      return;
    }
    const next: ParentGenotype = { ...value };
    const state = optionStateToZygosity(option, gene);
    next[option.geneKey] = state;
    setQuery('');
    setOpen(false);
    onChange(next);
    window.requestAnimationFrame(() => {
      inputRef.current?.focus();
    });
  };

  const handleRemove = (geneKey: string) => {
    const next: ParentGenotype = { ...value };
    delete next[geneKey];
    onChange(next);
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
      <div className="mm-chips">
        {selectedChips.map((chip) => (
          <button
            key={chip.gene.key}
            type="button"
            className="mm-chip"
            onClick={() => handleRemove(chip.gene.key)}
          >
            {chip.option.label}
            <span className="chip-remove" aria-hidden="true">
              ×
            </span>
          </button>
        ))}
      </div>
      <div className="search-field">
        <input
          ref={inputRef}
          className="mm-input"
          type="text"
          value={query}
          onChange={(event) => {
            setQuery(event.target.value);
            if (!open) {
              setOpen(true);
            }
          }}
          onFocus={handleInputFocus}
          onKeyDown={handleKeyDown}
          placeholder="Suche nach Traits"
          aria-expanded={open}
        />
      </div>
      {open && (
        <div className="options-dropdown" role="listbox">
          {grouped.length === 0 && <div className="dropdown-empty">Keine Treffer</div>}
          {grouped.map((entry) => (
            <div key={entry.group} className="options-group">
              <div className="options-group__label">{GROUP_LABELS[entry.group]}</div>
              <ul>
                {entry.options.map((option) => {
                  const index = indexMap.get(option) ?? 0;
                  const isActive = index === highlightIndex;
                  return (
                    <li key={`${option.geneKey}-${option.state}`}>
                      <button
                        type="button"
                        className={isActive ? 'dropdown-option is-active' : 'dropdown-option'}
                        onMouseEnter={() => setHighlightIndex(index)}
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => handleSelect(option)}
                      >
                        {option.label}
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
