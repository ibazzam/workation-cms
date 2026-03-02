import { BadRequestException, Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

type CurrencySettings = {
  baseCurrency: 'USD' | 'MVR';
  supportedCurrencies: Array<'USD' | 'MVR'>;
};

type ExchangeRate = {
  from: 'USD' | 'MVR';
  to: 'USD' | 'MVR';
  rate: number;
};

type ExchangeRatesSettings = {
  rates: ExchangeRate[];
  updatedAt: string;
};

type LoyaltySettings = {
  enabled: boolean;
  pointsPerUnitSpend: number;
  unitCurrency: 'USD' | 'MVR';
  redemptionValuePerPoint: number;
  minimumPointsToRedeem: number;
};

type CommercialSettings = {
  currency: CurrencySettings;
  exchangeRates: ExchangeRatesSettings;
  loyalty: LoyaltySettings;
};

const COMMERCIAL_SETTINGS_KEY = 'COMMERCIAL_SETTINGS';

@Injectable()
export class AdminSettingsService {
  constructor(private readonly prisma: PrismaService) {}

  async getCommercialSettings() {
    const record = await this.prisma.appConfig.findUnique({
      where: { key: COMMERCIAL_SETTINGS_KEY },
    });

    const parsed = record ? this.parseCommercialSettings(record.value) : this.defaultCommercialSettings();
    return {
      ...parsed,
      metadata: {
        updatedAt: record?.updatedAt?.toISOString() ?? null,
      },
    };
  }

  async updateCommercialSettings(payload: Record<string, unknown>) {
    const current = await this.getCommercialSettings();
    const merged = {
      currency: payload.currency ?? current.currency,
      exchangeRates: payload.exchangeRates ?? current.exchangeRates,
      loyalty: payload.loyalty ?? current.loyalty,
    };

    const normalized = this.normalizeCommercialSettings(merged as Record<string, unknown>);

    const upserted = await this.prisma.appConfig.upsert({
      where: { key: COMMERCIAL_SETTINGS_KEY },
      update: { value: normalized },
      create: { key: COMMERCIAL_SETTINGS_KEY, value: normalized },
    });

    return {
      ...normalized,
      metadata: {
        updatedAt: upserted.updatedAt.toISOString(),
      },
    };
  }

  private defaultCommercialSettings(): CommercialSettings {
    const now = new Date().toISOString();
    return {
      currency: {
        baseCurrency: 'USD',
        supportedCurrencies: ['USD', 'MVR'],
      },
      exchangeRates: {
        rates: [
          { from: 'USD', to: 'MVR', rate: 15.42 },
          { from: 'MVR', to: 'USD', rate: 1 / 15.42 },
        ],
        updatedAt: now,
      },
      loyalty: {
        enabled: false,
        pointsPerUnitSpend: 1,
        unitCurrency: 'USD',
        redemptionValuePerPoint: 0.01,
        minimumPointsToRedeem: 100,
      },
    };
  }

  private parseCommercialSettings(value: unknown): CommercialSettings {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      return this.defaultCommercialSettings();
    }

    return this.normalizeCommercialSettings(value as Record<string, unknown>);
  }

  private normalizeCommercialSettings(value: Record<string, unknown>): CommercialSettings {
    const currency = this.normalizeCurrencySettings(value.currency);
    const exchangeRates = this.normalizeExchangeRatesSettings(value.exchangeRates);
    const loyalty = this.normalizeLoyaltySettings(value.loyalty);

    return {
      currency,
      exchangeRates,
      loyalty,
    };
  }

  private normalizeCurrencySettings(value: unknown): CurrencySettings {
    const fallback = this.defaultCommercialSettings().currency;
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      return fallback;
    }

    const record = value as Record<string, unknown>;
    const baseCurrency = this.parseCurrencyCode(record.baseCurrency);
    if (!baseCurrency) {
      throw new BadRequestException('currency.baseCurrency must be USD or MVR');
    }

    if (!Array.isArray(record.supportedCurrencies)) {
      throw new BadRequestException('currency.supportedCurrencies must be an array');
    }

    const supportedCurrencies = record.supportedCurrencies
      .map((entry) => this.parseCurrencyCode(entry))
      .filter((entry): entry is 'USD' | 'MVR' => entry !== null);

    if (supportedCurrencies.length === 0) {
      throw new BadRequestException('currency.supportedCurrencies must include at least one valid currency');
    }

    if (!supportedCurrencies.includes(baseCurrency)) {
      throw new BadRequestException('currency.supportedCurrencies must include baseCurrency');
    }

    return {
      baseCurrency,
      supportedCurrencies: Array.from(new Set(supportedCurrencies)),
    };
  }

  private normalizeExchangeRatesSettings(value: unknown): ExchangeRatesSettings {
    const fallback = this.defaultCommercialSettings().exchangeRates;
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      return fallback;
    }

    const record = value as Record<string, unknown>;
    if (!Array.isArray(record.rates)) {
      throw new BadRequestException('exchangeRates.rates must be an array');
    }

    const rates: ExchangeRate[] = [];
    for (const item of record.rates) {
      if (!item || typeof item !== 'object' || Array.isArray(item)) {
        throw new BadRequestException('exchangeRates.rates entries must be objects');
      }

      const rateRecord = item as Record<string, unknown>;
      const from = this.parseCurrencyCode(rateRecord.from);
      const to = this.parseCurrencyCode(rateRecord.to);
      const rate = this.parsePositiveNumber(rateRecord.rate);

      if (!from || !to) {
        throw new BadRequestException('exchangeRates.rates entries must use USD/MVR currencies');
      }

      if (from === to) {
        throw new BadRequestException('exchangeRates.rates entries cannot have identical from/to currencies');
      }

      if (rate === null) {
        throw new BadRequestException('exchangeRates.rates.rate must be a positive number');
      }

      rates.push({ from, to, rate });
    }

    if (rates.length === 0) {
      throw new BadRequestException('exchangeRates.rates must include at least one entry');
    }

    const updatedAt = typeof record.updatedAt === 'string' && !Number.isNaN(new Date(record.updatedAt).getTime())
      ? new Date(record.updatedAt).toISOString()
      : new Date().toISOString();

    return {
      rates,
      updatedAt,
    };
  }

  private normalizeLoyaltySettings(value: unknown): LoyaltySettings {
    const fallback = this.defaultCommercialSettings().loyalty;
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      return fallback;
    }

    const record = value as Record<string, unknown>;
    const unitCurrency = this.parseCurrencyCode(record.unitCurrency);
    if (!unitCurrency) {
      throw new BadRequestException('loyalty.unitCurrency must be USD or MVR');
    }

    const pointsPerUnitSpend = this.parsePositiveNumber(record.pointsPerUnitSpend);
    const redemptionValuePerPoint = this.parsePositiveNumber(record.redemptionValuePerPoint);
    const minimumPointsToRedeem = this.parsePositiveInt(record.minimumPointsToRedeem);

    if (pointsPerUnitSpend === null) {
      throw new BadRequestException('loyalty.pointsPerUnitSpend must be a positive number');
    }

    if (redemptionValuePerPoint === null) {
      throw new BadRequestException('loyalty.redemptionValuePerPoint must be a positive number');
    }

    if (minimumPointsToRedeem === null) {
      throw new BadRequestException('loyalty.minimumPointsToRedeem must be a positive integer');
    }

    return {
      enabled: Boolean(record.enabled),
      pointsPerUnitSpend,
      unitCurrency,
      redemptionValuePerPoint,
      minimumPointsToRedeem,
    };
  }

  private parseCurrencyCode(value: unknown): 'USD' | 'MVR' | null {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (normalized === 'USD' || normalized === 'MVR') {
      return normalized;
    }

    return null;
  }

  private parsePositiveNumber(value: unknown): number | null {
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(parsed) || parsed <= 0) {
      return null;
    }

    return parsed;
  }

  private parsePositiveInt(value: unknown): number | null {
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      return null;
    }

    return parsed;
  }
}
