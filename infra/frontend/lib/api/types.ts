export type HealthStatus = {
  status: string;
  timestamp?: string;
};

export type Accommodation = {
  id: string;
  name: string;
  islandName?: string;
};

export type Island = {
  id: string;
  name: string;
  atollName?: string;
};

export type Vendor = {
  id: string;
  name: string;
};

export type Booking = {
  id: string;
  status: string;
  serviceType?: string;
  createdAt?: string;
  startDate?: string;
  endDate?: string;
  totalPrice?: number;
  currency?: string;
};

export type Transport = {
  id: string;
  name: string;
  fromIslandName?: string;
  toIslandName?: string;
  price: number;
  currency: string;
};

export type Excursion = {
  id: string;
  title: string;
  islandName?: string;
  price: number;
  currency: string;
};
