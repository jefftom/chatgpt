import { describe, expect, it } from 'vitest';
import { mergeTokens } from '../../src/types/tokens';

describe('mergeTokens', () => {
it('prefers override tokens', () => {
const base = { primary: '#000', gap: '1rem' };
const override = { gap: '2rem' };
expect(mergeTokens(base, override)).toEqual({ primary: '#000', gap: '2rem' });
});
});
