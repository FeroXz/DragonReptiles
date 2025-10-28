export type GeneType = 'recessive' | 'incomplete_dominant' | 'dominant' | 'polygenic';
export type Zygosity = 'normal' | 'het' | 'expressed' | 'super';

export interface GeneDef {
  key: string;
  name: string;
  type: GeneType;
  species: ('hognose' | 'pogona')[];
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
  genotype: Record<string, Zygosity>;
  phenotypeTokens: string[];
}
