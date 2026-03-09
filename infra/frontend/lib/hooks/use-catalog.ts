"use client";

import { useQuery } from '@tanstack/react-query';
import {
  fetchAccommodations,
  fetchBookings,
  fetchHealthStatus,
  fetchIslands,
  fetchVendors,
} from '../api/client';
import { queryKeys } from '../query/keys';

export function useHealthStatus() {
  return useQuery({
    queryKey: queryKeys.health,
    queryFn: fetchHealthStatus,
  });
}

export function useAccommodations() {
  return useQuery({
    queryKey: queryKeys.accommodations,
    queryFn: fetchAccommodations,
  });
}

export function useIslands() {
  return useQuery({
    queryKey: queryKeys.islands,
    queryFn: fetchIslands,
  });
}

export function useVendors() {
  return useQuery({
    queryKey: queryKeys.vendors,
    queryFn: fetchVendors,
  });
}

export function useBookings() {
  return useQuery({
    queryKey: queryKeys.bookings,
    queryFn: fetchBookings,
  });
}
