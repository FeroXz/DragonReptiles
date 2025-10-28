import { useCallback, useEffect, useMemo, useRef, useState, type KeyboardEventHandler } from 'react';
import clsx from 'clsx';
import { buildOptions, Option } from '@lib/genetics/options.js';
import { GeneDef, ParentGenotype, Zygosity } from '@lib/genetics/types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';
import translationsDe from '@i18n/genetics.de.json';

const GROUP_ORDER: Option['group'][] = ['inkomplett dominant', 'rezessiv', 'dominant', 'polygen'];

interface GeneticsMessages {
  searchPlaceholder: string;
  searchClear: string;
  searchNoMatches: string;
  chipRemove: string;
  incompatible: string;
  sectionTitles: Record<'incomplete_dominant' | 'dominant' | 'recessive' | 'polygenic', string>;
  het: string;
  super: string;
}

type SpeciesKey = 'hognose' | 'pogona';

type OptionEntry = Option & {
  disabled: boolean;
  score: number;
  badge: string | null;
};

type GroupedOptions = {
  group: Option['group'];
  label: string;
  options: OptionEntry[];
};

const messages = translationsDe as GeneticsMessages;

const SPECIES_GENES: Record<SpeciesKey, GeneDef[]> = {
  hognose: (hognoseGenes as GeneDef[]).map((gene) => ({ ...gene })),
  pogona: (pogonaGenes as GeneDef[]).map((gene) => ({ ...gene }))
};

function toZygosity(entry: ParentGenotype[string]): Zygosity | undefined {
  if (!entry) {
    return undefined;
  }
  if (typeof entry === 'string') {
    return entry;
  }
  return entry.state;
}

function isSuperState(gene: GeneDef, state: Zygosity): boolean {
  if (gene.type === 'recessive') {
    return state === 'expressed';
  }
  if (gene.type === 'incomplete_dominant') {
    return state === 'super';
  }
  return false;
}

function resolveOptionState(gene: GeneDef, state: Zygosity): Option['state'] | null {
  if (gene.type === 'recessive') {
    if (state === 'het' || state === 'expressed') {
      return state;
    }
    return null;
  }
  if (gene.type === 'incomplete_dominant') {
    if (state === 'expressed' || state === 'super') {
      return state;
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

function optionCreatesSuperState(option: Option, gene: GeneDef | undefined): boolean {
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

function shouldDisableOption(option: Option, gene: GeneDef | undefined, value: ParentGenotype, geneMap: Map<string, GeneDef>): boolean {
  if (!gene || !Array.isArray(gene.incompatibleWith) || gene.incompatibleWith.length === 0) {
    return false;
  }
  if (!optionCreatesSuperState(option, gene)) {
    return false;
  }
  return gene.incompatibleWith.some((otherKey) => {
    const otherGene = geneMap.get(otherKey);
    if (!otherGene) {
      return false;
    }
    const currentState = toZygosity(value[otherKey]);
    if (!currentState) {
      return false;
    }
    return isSuperState(otherGene, currentState);
  });
}

function mapGroupLabel(group: Option['group']): string {
  switch (group) {
    case 'inkomplett dominant':
      return messages.sectionTitles.incomplete_dominant;
    case 'rezessiv':
      return messages.sectionTitles.recessive;
    case 'dominant':
      return messages.sectionTitles.dominant;
    case 'polygen':
      return messages.sectionTitles.polygenic;
    default:
      return group;
  }
}

function getMatchScore(option: Option, term: string): number {
  if (!term) {
    return 100;
  }
  const normalizedLabel = option.label.toLowerCase();
  const keywords = option.keywords.map((keyword) => keyword.toLowerCase());
  if (normalizedLabel.startsWith(term)) {
    return 0;
  }
  if (keywords.some((keyword) => keyword.startsWith(term))) {
    return 1;
  }
  if (normalizedLabel.includes(term)) {
    return 2;
  }
  if (keywords.some((keyword) => keyword.includes(term))) {
    return 3;
  }
  return Number.POSITIVE_INFINITY;
}

function normalizeTerm(value: string): string {
  return value.trim().toLowerCase();
}

interface SelectedChip {
  geneKey: string;
  label: string;
}

function getOptionBadge(option: Option): string | null {
  if (option.group === 'rezessiv') {
    return option.state === 'het' ? messages.het : messages.sectionTitles.recessive;
  }
  if (option.group === 'inkomplett dominant') {
    return option.state === 'super' ? messages.super : messages.sectionTitles.incomplete_dominant;
  }
  if (option.group === 'dominant') {
    return messages.sectionTitles.dominant;
  }
  if (option.group === 'polygen') {
    return messages.sectionTitles.polygenic;
  }
  return null;
}

export interface GenotypeSearchProps {
  species: SpeciesKey;
  value: ParentGenotype;
  onChange: (next: ParentGenotype) => void;
}

export function GenotypeSearch({ species, value, onChange }: GenotypeSearchProps) {
  const [query, setQuery] = useState('');
  const [isFocused, setIsFocused] = useState(false);
  const [activeIndex, setActiveIndex] = useState(-1);
  const inputRef = useRef<HTMLInputElement>(null);

  const genes = useMemo(() => SPECIES_GENES[species] ?? [], [species]);
  const options = useMemo(() => buildOptions(genes), [genes]);
  const geneMap = useMemo(() => new Map(genes.map((gene) => [gene.key, gene])), [genes]);
  const optionMap = useMemo(() => {
    const map = new Map<string, Option>();
    for (const option of options) {
      map.set(`${option.geneKey}:${option.state}`, option);
    }
    return map;
  }, [options]);

  const selectedChips = useMemo<SelectedChip[]>(() => {
    const chips: SelectedChip[] = [];
    for (const [geneKey, entry] of Object.entries(value)) {
      const gene = geneMap.get(geneKey);
      if (!gene) {
        continue;
      }
      const state = toZygosity(entry);
      if (!state || state === 'normal') {
        continue;
      }
      const optionState = resolveOptionState(gene, state);
      if (!optionState) {
        continue;
      }
      const option = optionMap.get(`${geneKey}:${optionState}`);
      if (!option) {
        continue;
      }
      chips.push({ geneKey, label: option.label });
    }
    chips.sort((a, b) => a.label.localeCompare(b.label, 'de'));
    return chips;
  }, [geneMap, optionMap, value]);

  const filteredOptions = useMemo(() => {
    const term = normalizeTerm(query);
    const enriched: OptionEntry[] = [];
    for (const option of options) {
      const score = getMatchScore(option, term);
      if (score === Number.POSITIVE_INFINITY) {
        if (term) {
          continue;
        }
      }
      const gene = geneMap.get(option.geneKey);
      const disabled = shouldDisableOption(option, gene, value, geneMap);
      enriched.push({ ...option, disabled, score, badge: getOptionBadge(option) });
    }
    enriched.sort((a, b) => {
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
    return enriched;
  }, [geneMap, options, query, value]);

  const groupedOptions = useMemo<GroupedOptions[]>(() => {
    const groups: GroupedOptions[] = [];
    for (const group of GROUP_ORDER) {
      const optionsInGroup = filteredOptions.filter((option) => option.group === group);
      if (optionsInGroup.length > 0) {
        groups.push({ group, label: mapGroupLabel(group), options: optionsInGroup });
      }
    }
    return groups;
  }, [filteredOptions]);

  const flatList = useMemo(() => groupedOptions.flatMap((group) => group.options), [groupedOptions]);
  const showSuggestions = (isFocused || Boolean(query)) && (groupedOptions.length > 0 || normalizeTerm(query).length > 0);

  useEffect(() => {
    setQuery('');
    setActiveIndex(-1);
  }, [species]);

  const handleSelect = useCallback(
    (option: OptionEntry) => {
      if (option.disabled) {
        return;
      }
      const next: ParentGenotype = { ...value };
      if (option.group === 'polygen') {
        next[option.geneKey] = 'expressed';
      } else if (option.group === 'dominant') {
        next[option.geneKey] = 'expressed';
      } else if (option.group === 'rezessiv') {
        next[option.geneKey] = option.state === 'het' ? 'het' : 'expressed';
      } else if (option.group === 'inkomplett dominant') {
        next[option.geneKey] = option.state === 'super' ? 'super' : 'expressed';
      }
      onChange(next);
      setQuery('');
      setActiveIndex(-1);
      requestAnimationFrame(() => {
        inputRef.current?.focus();
      });
    },
    [onChange, value]
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
      if (!flatList.length) {
        return;
      }
      setActiveIndex((prev) => {
        let next = prev;
        for (let step = 0; step < flatList.length; step += 1) {
          next = (next + delta + flatList.length) % flatList.length;
          if (!flatList[next].disabled) {
            break;
          }
        }
        return next;
      });
    },
    [flatList]
  );

  const handleKeyDown: KeyboardEventHandler<HTMLInputElement> = (event) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      if (activeIndex === -1 && flatList.length) {
        const firstEnabled = flatList.findIndex((option) => !option.disabled);
        setActiveIndex(firstEnabled === -1 ? -1 : firstEnabled);
      } else {
        moveActive(1);
      }
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (activeIndex === -1 && flatList.length) {
        const lastEnabled = [...flatList].reverse().findIndex((option) => !option.disabled);
        if (lastEnabled !== -1) {
          setActiveIndex(flatList.length - 1 - lastEnabled);
        }
      } else {
        moveActive(-1);
      }
    } else if (event.key === 'Enter') {
      if (activeIndex >= 0 && activeIndex < flatList.length) {
        event.preventDefault();
        handleSelect(flatList[activeIndex]);
      }
    } else if (event.key === 'Escape') {
      if (query) {
        event.preventDefault();
        setQuery('');
        setActiveIndex(-1);
      }
    }
  };

  return (
    <section className="genotype-search">
      <div className="genotype-search__field" data-focused={isFocused || undefined}>
        <span className="genotype-search__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false">
            <path
              d="M15.5 14h-.79l-.28-.27a6.471 6.471 0 0 0 1.57-4.23 6.5 6.5 0 1 0-6.5 6.5 6.47 6.47 0 0 0 4.23-1.57l.27.28v.79l5 5 1.5-1.5-5-5zM10.5 14a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"
              />
          </svg>
        </span>
        <input
          ref={inputRef}
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
        />
        {query ? (
          <button
            type="button"
            className="genotype-search__clear"
            onClick={() => {
              setQuery('');
              setActiveIndex(-1);
              inputRef.current?.focus();
            }}
            aria-label={messages.searchClear}
          >
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
              <path d="m12 10.586 4.95-4.95 1.414 1.414L13.414 12l4.95 4.95-1.414 1.414L12 13.414l-4.95 4.95-1.414-1.414L10.586 12 5.636 7.05l1.414-1.414z" />
            </svg>
          </button>
        ) : null}
      </div>
      {showSuggestions ? (
        groupedOptions.length ? (
          <div className="genotype-search__suggestions">
            {groupedOptions.map((group) => (
              <div key={group.group} className="genotype-search__group">
                <div className="genotype-search__group-label">{group.label}</div>
                <ul role="listbox">
                  {group.options.map((option) => {
                    const flatIndex = flatList.indexOf(option);
                    const isActive = flatIndex === activeIndex;
                    return (
                      <li key={`${option.geneKey}:${option.state}`}>
                        <button
                          type="button"
                          role="option"
                        className={clsx('genotype-search__option', {
                          'is-disabled': option.disabled,
                          'is-active': isActive
                        })}
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => handleSelect(option)}
                        aria-disabled={option.disabled || undefined}
                        aria-selected={isActive || undefined}
                        title={option.disabled ? messages.incompatible : undefined}
                        >
                          <span className="genotype-search__option-label">{option.label}</span>
                          {option.badge ? (
                            <span className="genotype-search__badge" aria-hidden="true">
                              {option.badge}
                            </span>
                          ) : null}
                        </button>
                      </li>
                    );
                  })}
                </ul>
              </div>
            ))}
          </div>
        ) : (
          <div className="genotype-search__no-results">{messages.searchNoMatches}</div>
        )
      ) : null}
      {selectedChips.length ? (
        <div className="genotype-search__chips">
          {selectedChips.map((chip) => (
            <span key={chip.geneKey} className="genotype-search__chip">
              <span>{chip.label}</span>
              <button type="button" onClick={() => handleRemove(chip.geneKey)} aria-label={`${chip.label} ${messages.chipRemove}`}>
                ×
              </button>
            </span>
          ))}
        </div>
      ) : null}
    </section>
  );
}
