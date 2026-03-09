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
};
