export type TokenMap = Record<string, string>;

export function mergeTokens(base: TokenMap, override: TokenMap): TokenMap {
return { ...base, ...override };
}
