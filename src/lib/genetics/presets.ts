import { SpeciesKey, Zygosity } from './types.js';
import hognosePresets from '@data/search-presets/hognose.json';
import pogonaPresets from '@data/search-presets/pogona.json';

export interface SearchPresetGeneState {
  key: string;
  state: Zygosity;
  posHet?: number;
}

export interface SearchPreset {
  label: string;
  genes: SearchPresetGeneState[];
  keywords?: string[];
}

const RAW_PRESETS: Record<SpeciesKey, SearchPreset[]> = {
  hognose: (hognosePresets as SearchPreset[]).map((preset) => ({ ...preset })),
  pogona: (pogonaPresets as SearchPreset[]).map((preset) => ({ ...preset }))
};

export function getSearchPresets(species: SpeciesKey): SearchPreset[] {
  return RAW_PRESETS[species] ?? [];
}
