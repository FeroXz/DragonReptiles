export type SpeciesKey = 'hognose' | 'pogona';
export type GeneType = 'recessive' | 'incomplete_dominant' | 'dominant' | 'polygenic';
export type Zygosity = 'normal' | 'het' | 'expressed' | 'super';

export interface GeneDef {
  key: string;
  name: string;
  type: GeneType;
  species: SpeciesKey[];
  aliases?: string[];
  notes?: string;
  incompatibleWith?: string[];
  visible?: boolean;
  superLabel?: string;
  searchAliases?: string[];
}

export interface ParentGenotype {
  [geneKey: string]: Zygosity | { state: Zygosity; posHet?: number };
}

export interface PairingResult {
  probability: number;
  genotype: Record<string, Zygosity | { state: Zygosity; posHet?: number }>;
  phenotypeTokens: string[];
}
