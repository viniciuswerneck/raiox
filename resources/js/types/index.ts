export interface LocationReport {
  cep: string;
  logradouro: string;
  bairro: string;
  cidade: string;
  uf: string;
  codigo_ibge: string | null;
  lat: number | null;
  lng: number | null;
  status: 'processing' | 'completed' | 'failed' | 'processing_text';
  walkability_score: number | null;
  air_quality_index: number | null;
  population: number | null;
  idhm: number | null;
  general_score: number | null;
  safety_level: string | null;
  safety_description: string | null;
  data_version: number;
  created_at: string;
  updated_at: string;
}

export interface CityData {
  name: string;
  slug: string;
  state: string;
  ibge_code: string;
  population: number | null;
  idhm: number | null;
  report_count: number;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  status: 'success' | 'error';
}

export interface Suggestion {
  cep?: string;
  address: string;
  city: string;
  state: string;
  type: 'cep' | 'city';
}

export interface HealthStatus {
  status: 'ok' | 'degraded' | 'unhealthy';
  timestamp: string;
  services: {
    database: ServiceStatus;
    cache: ServiceStatus;
    apis: Record<string, ApiServiceStatus>;
    rate_limits: Record<string, RateLimitStatus>;
  };
}

export interface ServiceStatus {
  status: 'healthy' | 'unhealthy';
  latency_ms?: number;
  error?: string;
}

export interface ApiServiceStatus {
  status: 'healthy' | 'unhealthy' | 'unreachable';
  latency_ms: number | null;
  http_code: number | null;
  message: string;
}

export interface RateLimitStatus {
  name: string;
  used: number;
  max: number;
  remaining: number;
  window_seconds: number;
  percentage: number;
  is_limited: boolean;
}
