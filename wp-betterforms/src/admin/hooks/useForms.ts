import { useCallback, useEffect, useState } from 'react';
import type { FormResponse, FormSummary } from '../types';

const config = (window as any).wpBetterFormsConfig ?? {};
const apiBase: string = config.restBase ?? '';
const nonce: string = config.nonce ?? '';

export function useForms() {
  const [forms, setForms] = useState<FormSummary[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchForms = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await fetch(apiBase, {
        headers: {
          'X-WP-Nonce': nonce,
        },
      });
      if (!response.ok) {
        throw new Error('Unable to load forms');
      }
      const data = (await response.json()) as FormResponse;
      setForms(data.forms);
    } catch (err) {
      setError((err as Error).message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchForms().catch((error) => {
      console.error('Failed to fetch forms', error);
    });
  }, [fetchForms]);

  return { forms, loading, error, refresh: fetchForms };
}

export function getNonce() {
  return nonce;
}

export function getApiBase() {
  return apiBase;
}
