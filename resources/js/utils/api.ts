import axios, { AxiosResponse } from 'axios';
import type { 
  LocationReport, 
  ApiResponse, 
  Suggestion, 
  HealthStatus 
} from './types';

const api = axios.create({
  baseURL: '/',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export async function searchCep(cep: string): Promise<LocationReport | null> {
  try {
    const response: AxiosResponse<ApiResponse<{ redirect_url: string }>> = 
      await api.post('/search', { cep });
    
    if (response.data.status === 'success' && response.data.data.redirect_url) {
      const cepMatch = response.data.data.redirect_url.match(/\/cep\/(\d+)/);
      if (cepMatch) {
        return getReportByCep(cepMatch[1]);
      }
    }
    return null;
  } catch (error) {
    console.error('Search error:', error);
    return null;
  }
}

export async function getReportByCep(cep: string): Promise<LocationReport | null> {
  try {
    const response: AxiosResponse<LocationReport> = 
      await api.get(`/api/report-data/${cep}`);
    return response.data;
  } catch (error) {
    console.error('Get report error:', error);
    return null;
  }
}

export async function getSuggestions(query: string): Promise<Suggestion[]> {
  try {
    const response: AxiosResponse<{ suggestions: Suggestion[] }> = 
      await api.get('/suggestions', { params: { q: query } });
    return response.data.suggestions;
  } catch (error) {
    console.error('Suggestions error:', error);
    return [];
  }
}

export async function getReportStatus(cep: string): Promise<string> {
  try {
    const response: AxiosResponse<{ status: string }> = 
      await api.get(`/api/report-status/${cep}`);
    return response.data.status;
  } catch (error) {
    console.error('Status check error:', error);
    return 'error';
  }
}

export async function checkHealth(): Promise<HealthStatus | null> {
  try {
    const response: AxiosResponse<HealthStatus> = 
      await api.get('/health');
    return response.data;
  } catch (error) {
    console.error('Health check error:', error);
    return null;
  }
}

export function formatCep(cep: string): string {
  const cleaned = cep.replace(/\D/g, '');
  if (cleaned.length === 8) {
    return `${cleaned.slice(0, 5)}-${cleaned.slice(5)}`;
  }
  return cep;
}

export function parseCep(cep: string): string {
  return cep.replace(/\D/g, '');
}

export { api };
