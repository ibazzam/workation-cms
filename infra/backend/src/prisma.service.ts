import { INestApplication, Injectable, OnModuleInit } from '@nestjs/common';
import { PrismaClient } from '@prisma/client';

@Injectable()
export class PrismaService extends PrismaClient implements OnModuleInit {
  async onModuleInit() {
    await this.$connect();
  }

  async enableShutdownHooks(app: INestApplication) {
    // PrismaClient's `$on` type can be narrow; cast to `any` to allow
    // registering the `beforeExit` hook without changing runtime behavior.
    (this as any).$on('beforeExit', async () => {
      await app.close();
    });
  }
}
