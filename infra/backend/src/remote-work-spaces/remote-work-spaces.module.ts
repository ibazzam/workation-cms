import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { RemoteWorkSpacesController } from './remote-work-spaces.controller';
import { RemoteWorkSpacesService } from './remote-work-spaces.service';

@Module({
  controllers: [RemoteWorkSpacesController],
  providers: [RemoteWorkSpacesService, PrismaService],
})
export class RemoteWorkSpacesModule {}
