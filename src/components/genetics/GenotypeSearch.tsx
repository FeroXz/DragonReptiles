import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type KeyboardEventHandler
} from 'react';
import clsx from 'clsx';
import { buildOptions, type Option } from '@lib/genetics/options.js';
import { GeneDef, ParentGenotype, Zygosity } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import translationsDe from '@i18n/genetics.de.json';

type SpeciesKey = 'hognose' | 'pogona';

type GroupLabelMap = Record<Option['group'], string>;

type GroupedOptions = {
  group: Option['group'];
  label: string;
  options: OptionEntry[];
};

type OptionEntry = Option & {
  disabled: boolean;
  score: number;
};

interface SelectedOption {
  geneKey: string;
  label: string;
}

interface GeneticsMessages {
  searchPlaceholder: string;
  searchClear: string;
  searchNoMatches: string;
  chipRemove: string;
  incompatible: string;
  sectionTitles: Record<'incomplete_dominant' | 'dominant' | 'recessive' | 'polygenic', string>;
}

export interface GenotypeSearchProps {
  species: SpeciesKey;
  value: ParentGenotype;
  onChange: (next: ParentGenotype) => void;
}

const messages = translationsDe as GeneticsMessages;

const GROUP_ORDER: Option['group'][] = ['id', 'recessive', 'dominant', 'poly'];

const SPECIES_GENES: Record<SpeciesKey, GeneDef[]> = {
  hognose: (hognoseGenes as GeneDef[]).map((gene) => ({ ...gene })),
  pogona: (pogonaGenes as GeneDef[]).map((gene) => ({ ...gene }))
};

function resolveGroupLabel(group: Option['group']): string {
  const map: GroupLabelMap = {
    id: messages.sectionTitles.incomplete_dominant,
    recessive: messages.sectionTitles.recessive,
    dominant: messages.sectionTitles.dominant,
    poly: messages.sectionTitles.polygenic
  };
  return map[group];
}

function toZygosity(entry: ParentGenotype[string]): Zygosity | undefined {
  if (!entry) {
    return undefined;
  }
  if (typeof entry === 'string') {
    return entry;
  }
  return entry.state;
}

function optionFromState(gene: GeneDef, state: Zygosity): Option['state'] | null {
  if (state === 'normal') {
    return null;
  }
  if (gene.type === 'recessive') {
    if (state === 'het' || state === 'expressed') {
      return state;
    }
    return null;
  }
  if (gene.type === 'incomplete_dominant') {
    if (state === 'expressed') {
      return 'expressed';
    }
    if (state === 'super') {
      return 'super';
    }
    return null;
  }
  if (gene.type === 'dominant') {
    return state === 'expressed' ? 'present' : null;
  }
  if (gene.type === 'polygenic') {
    return state === 'expressed' ? 'poly' : null;
  }
  return null;
}

function optionCreatesSuper(option: Option, gene: GeneDef | undefined): boolean {
  if (!gene) {
    return false;
  }
  if (gene.type === 'recessive') {
    return option.state === 'expressed';
  }
  if (gene.type === 'incomplete_dominant') {
    return option.state === 'super';
  }
  return false;
}

function shouldDisableOption(
  option: Option,
  gene: GeneDef | undefined,
  genotype: ParentGenotype,
  geneMap: Map<string, GeneDef>
): boolean {
  if (!gene || !gene.incompatibleWith || !optionCreatesSuper(option, gene)) {
    return false;
  }
  return gene.incompatibleWith.some((otherKey) => {
    const otherGene = geneMap.get(otherKey);
    if (!otherGene) {
      return false;
    }
    const currentState = toZygosity(genotype[otherKey]);
    if (!currentState) {
      return false;
    }
    if (otherGene.type === 'recessive') {
      return currentState === 'expressed';
    }
    if (otherGene.type === 'incomplete_dominant') {
      return currentState === 'super';
    }
    return false;
  });
}

function scoreOption(option: Option, term: string): number {
  if (!term) {
    return 0;
  }
  const normalizedLabel = option.label.toLowerCase();
  if (normalizedLabel.startsWith(term)) {
    return 0;
  }
  if (option.keywords.some((keyword) => keyword.startsWith(term))) {
    return 1;
  }
  if (normalizedLabel.includes(term)) {
    return 2;
  }
  if (option.keywords.some((keyword) => keyword.includes(term))) {
    return 3;
  }
  return Number.POSITIVE_INFINITY;
}

export function GenotypeSearch({ species, value, onChange }: GenotypeSearchProps) {
  const [query, setQuery] = useState('');
  const [activeIndex, setActiveIndex] = useState(-1);
  const [isFocused, setIsFocused] = useState(false);
  const [term, setTerm] = useState('');

  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const timeout = window.setTimeout(() => {
      setTerm(query.trim().toLowerCase());
    }, 120);
    return () => window.clearTimeout(timeout);
  }, [query]);

  const genes = useMemo(() => SPECIES_GENES[species] ?? [], [species]);
  const geneMap = useMemo(() => new Map(genes.map((gene) => [gene.key, gene])), [genes]);
  const options = useMemo(() => buildOptions(genes), [genes]);
  const optionIndex = useMemo(() => {
    const map = new Map<string, Option>();
    options.forEach((option) => {
      map.set(`${option.geneKey}:${option.state}`, option);
    });
    return map;
  }, [options]);

  const selected = useMemo<SelectedOption[]>(() => {
    const list: SelectedOption[] = [];
    Object.entries(value).forEach(([geneKey, entry]) => {
      const gene = geneMap.get(geneKey);
      if (!gene) {
        return;
      }
      const state = toZygosity(entry);
      if (!state) {
        return;
      }
      const optionState = optionFromState(gene, state);
      if (!optionState) {
        return;
      }
      const option = optionIndex.get(`${geneKey}:${optionState}`);
      if (!option) {
        return;
      }
      list.push({ geneKey, label: option.label });
    });
    return list.sort((a, b) => a.label.localeCompare(b.label, 'de'));
  }, [geneMap, optionIndex, value]);

  const enrichedOptions = useMemo<OptionEntry[]>(() => {
    return options
      .map<OptionEntry>((option) => {
        const gene = geneMap.get(option.geneKey);
        return {
          ...option,
          disabled: shouldDisableOption(option, gene, value, geneMap),
          score: scoreOption(option, term)
        };
      })
      .filter((entry) => entry.score !== Number.POSITIVE_INFINITY || !term)
      .sort((a, b) => {
        if (a.disabled !== b.disabled) {
          return Number(a.disabled) - Number(b.disabled);
        }
        if (a.score !== b.score) {
          return a.score - b.score;
        }
        if (a.group !== b.group) {
          return GROUP_ORDER.indexOf(a.group) - GROUP_ORDER.indexOf(b.group);
        }
        return a.label.localeCompare(b.label, 'de');
      });
  }, [geneMap, options, term, value]);

  const grouped = useMemo<GroupedOptions[]>(() => {
    return GROUP_ORDER.map((group) => ({
      group,
      label: resolveGroupLabel(group),
      options: enrichedOptions.filter((option) => option.group === group)
    })).filter((group) => group.options.length > 0);
  }, [enrichedOptions]);

  const flat = useMemo(() => grouped.flatMap((group) => group.options), [grouped]);

  useEffect(() => {
    setQuery('');
    setTerm('');
    setActiveIndex(-1);
  }, [species]);

  const showDropdown = (isFocused || Boolean(term) || Boolean(query)) && flat.length > 0;

  const focusInput = useCallback(() => {
    requestAnimationFrame(() => {
      inputRef.current?.focus();
    });
  }, []);

  const handleSelect = useCallback(
    (option: OptionEntry) => {
      if (option.disabled) {
        return;
      }
      const next: ParentGenotype = { ...value };
      if (option.group === 'poly' || option.group === 'dominant') {
        next[option.geneKey] = 'expressed';
      } else if (option.group === 'recessive') {
        next[option.geneKey] = option.state === 'het' ? 'het' : 'expressed';
      } else if (option.group === 'id') {
        next[option.geneKey] = option.state === 'super' ? 'super' : 'expressed';
      }
      onChange(next);
      setQuery('');
      setTerm('');
      setActiveIndex(-1);
      focusInput();
    },
    [focusInput, onChange, value]
  );

  const handleRemove = useCallback(
    (geneKey: string) => {
      const next: ParentGenotype = { ...value };
      delete next[geneKey];
      onChange(next);
    },
    [onChange, value]
  );

  const moveActive = useCallback(
    (delta: number) => {
      if (!flat.length) {
        return;
      }
      setActiveIndex((prev) => {
        let next = prev;
        for (let step = 0; step < flat.length; step += 1) {
          next = (next + delta + flat.length) % flat.length;
          if (!flat[next].disabled) {
            break;
          }
        }
        return next;
      });
    },
    [flat]
  );

  const handleKeyDown: KeyboardEventHandler<HTMLInputElement> = (event) => {
    if (!flat.length) {
      return;
    }
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      if (activeIndex === -1) {
        const firstEnabled = flat.findIndex((option) => !option.disabled);
        setActiveIndex(firstEnabled);
      } else {
        moveActive(1);
      }
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (activeIndex === -1) {
        const lastEnabled = [...flat].reverse().findIndex((option) => !option.disabled);
        if (lastEnabled !== -1) {
          setActiveIndex(flat.length - 1 - lastEnabled);
        }
      } else {
        moveActive(-1);
      }
    } else if (event.key === 'Enter') {
      if (activeIndex >= 0 && activeIndex < flat.length) {
        event.preventDefault();
        handleSelect(flat[activeIndex]);
      }
    } else if (event.key === 'Escape') {
      if (query) {
        event.preventDefault();
        setQuery('');
        setTerm('');
        setActiveIndex(-1);
      }
    }
  };

  return (
    <section className="mm-search">
      <div className={clsx('mm-search-bar', { 'is-open': showDropdown })}>
        <input
          ref={inputRef}
          className="mm-input"
          type="text"
          value={query}
          placeholder={messages.searchPlaceholder}
          onChange={(event) => {
            setQuery(event.target.value);
            setActiveIndex(-1);
          }}
          onFocus={() => setIsFocused(true)}
          onBlur={() => setIsFocused(false)}
          onKeyDown={handleKeyDown}
          aria-autocomplete="list"
          aria-expanded={showDropdown || undefined}
          aria-controls="mm-search-options"
        />
        {query ? (
          <button
            type="button"
            className="mm-clear"
            onClick={() => {
              setQuery('');
              setTerm('');
              setActiveIndex(-1);
              focusInput();
            }}
            aria-label={messages.searchClear}
          >
            ×
          </button>
        ) : null}
      </div>

      {selected.length ? (
        <div className="mm-chips" role="list">
          {selected.map((entry) => (
            <span key={entry.geneKey} className="mm-chip" role="listitem">
              <span>{entry.label}</span>
              <button type="button" onClick={() => handleRemove(entry.geneKey)} aria-label={`${entry.label} ${messages.chipRemove}`}>
                ×
              </button>
            </span>
          ))}
        </div>
      ) : null}

      {showDropdown ? (
        <div className="mm-dropdown" role="listbox" id="mm-search-options">
          {grouped.length ? (
            grouped.map((group) => (
              <div key={group.group} className="mm-group">
                <div className="mm-group-label">{group.label}</div>
                <ul>
                  {group.options.map((option) => {
                    const index = flat.indexOf(option);
                    const isActive = index === activeIndex;
                    return (
                      <li key={`${option.geneKey}:${option.state}`}>
                        <button
                          type="button"
                          role="option"
                          className={clsx('mm-option', {
                            'is-disabled': option.disabled,
                            'is-active': isActive
                          })}
                          onMouseDown={(event) => event.preventDefault()}
                          onClick={() => handleSelect(option)}
                          aria-disabled={option.disabled || undefined}
                          aria-selected={isActive || undefined}
                          title={option.disabled ? messages.incompatible : undefined}
                        >
                          <span className="mm-option-label">{option.label}</span>
                        </button>
                      </li>
                    );
                  })}
                </ul>
              </div>
            ))
          ) : (
            <div className="mm-no-results">{messages.searchNoMatches}</div>
          )}
        </div>
      ) : null}
    </section>
  );
}

export default GenotypeSearch;
