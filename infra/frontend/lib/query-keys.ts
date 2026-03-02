export const queryKeys = {
  workations: (apiBase: string) => ['workations', apiBase] as const,
  islands: (apiBase: string) => ['islands', apiBase] as const,
  vendors: (apiBase: string) => ['vendors', apiBase] as const,
  backendStatus: (apiBase: string) => ['backend-status', apiBase] as const,
}
