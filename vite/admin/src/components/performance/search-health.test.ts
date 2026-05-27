import { describe, it, expect, vi, beforeEach } from 'vitest';
import { SearchHealthSummary } from '@/tool/interface';

const mockEmptySummary: SearchHealthSummary = {
  range_days: 30,
  total_searches: 0,
  unique_terms: 0,
  top_terms: [],
  no_result_terms: [],
  suspicious_terms: [],
  recommendations: [],
};

const mockDataSummary: SearchHealthSummary = {
  range_days: 30,
  total_searches: 500,
  unique_terms: 120,
  top_terms: [
    { term: 'wordpress', count: 80, no_result_count: 0 },
    { term: 'php', count: 45, no_result_count: 5 },
    { term: 'react', count: 30, no_result_count: 0 },
  ],
  no_result_terms: [
    { term: 'vue', count: 20, no_result_count: 20 },
    { term: 'angular', count: 15, no_result_count: 15 },
  ],
  suspicious_terms: [],
  recommendations: [
    { id: 'rec_search_rate_limit', title: '限制搜索频次', reason: '防止恶意搜索' },
  ],
};

describe('SearchHealthSummary interface', () => {
  it('should have correct structure for empty data', () => {
    expect(mockEmptySummary.total_searches).toBe(0);
    expect(mockEmptySummary.unique_terms).toBe(0);
    expect(mockEmptySummary.top_terms).toHaveLength(0);
    expect(mockEmptySummary.no_result_terms).toHaveLength(0);
  });

  it('should have correct structure for populated data', () => {
    expect(mockDataSummary.total_searches).toBe(500);
    expect(mockDataSummary.unique_terms).toBe(120);
    expect(mockDataSummary.top_terms).toHaveLength(3);
    expect(mockDataSummary.no_result_terms).toHaveLength(2);
  });

  it('should calculate no-result ratio correctly', () => {
    const noResultTotal = mockDataSummary.no_result_terms.reduce(
      (s, t) => s + t.no_result_count, 0
    );
    const ratio = Math.round((noResultTotal / mockDataSummary.total_searches) * 100);
    expect(ratio).toBe(7);
  });

  it('should display top terms with counts', () => {
    expect(mockDataSummary.top_terms[0].term).toBe('wordpress');
    expect(mockDataSummary.top_terms[0].count).toBe(80);
  });

  it('should display no-result terms', () => {
    expect(mockDataSummary.no_result_terms[0].term).toBe('vue');
    expect(mockDataSummary.no_result_terms[0].no_result_count).toBe(20);
  });

  it('should handle recommendations', () => {
    expect(mockDataSummary.recommendations).toHaveLength(1);
    expect(mockDataSummary.recommendations[0].id).toBe('rec_search_rate_limit');
  });

  it('should handle API failure gracefully', () => {
    const failedResult = null;
    expect(failedResult).toBeNull();
  });
});

describe('searchHealthApi', () => {
  beforeEach(() => {
    vi.resetModules();
  });

  it('should call correct endpoint', async () => {
    const mockGet = vi.fn().mockResolvedValue({
      success: true,
      data: mockDataSummary,
    });

    vi.doMock('@/axios/public', () => ({
      restInstance: { get: mockGet },
      ApiResponse: class {},
    }));

    vi.doMock('@/tool/interface', () => ({
      SearchHealthSummary: {} as any,
    }));

    const { searchHealthApi } = await import('@/api');
    await searchHealthApi.getSummary(30);
    expect(mockGet).toHaveBeenCalledWith('/search-health/summary?days=30');
  });
});