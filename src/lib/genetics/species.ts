import { GeneDef, SpeciesKey } from './types.js';
import hognoseGenes from '@data/genes/hognose.json';
import pogonaGenes from '@data/genes/pogona.json';

const RAW_SPECIES_GENES: Record<SpeciesKey, GeneDef[]> = {
  hognose: (hognoseGenes as GeneDef[]).map((gene) => ({ ...gene })),
  pogona: (pogonaGenes as GeneDef[]).map((gene) => ({ ...gene }))
};

export function getGenesForSpecies(species: SpeciesKey): GeneDef[] {
  return RAW_SPECIES_GENES[species] ?? [];
}
