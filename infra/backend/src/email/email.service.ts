import { Injectable } from '@nestjs/common';
import * as nodemailer from 'nodemailer';

@Injectable()
export class EmailService {
  private transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: Number(process.env.SMTP_PORT) || 587,
    secure: false,
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS,
    },
  });

  async sendPasswordReset(email: string, resetLink: string) {
    return this.transporter.sendMail({
      from: process.env.SMTP_FROM || 'no-reply@workation.mv',
      to: email,
      subject: 'Workation: Password Reset',
      html: `<h2>Password Reset</h2><p>Click <a href="${resetLink}">here</a> to set your password.</p>`,
    });
  }

  async sendWelcome(email: string, name: string, resetLink: string) {
    return this.transporter.sendMail({
      from: process.env.SMTP_FROM || 'no-reply@workation.mv',
      to: email,
      subject: 'Welcome to Workation',
      html: `<h2>Welcome, ${name}!</h2><p>Your account has been created. Click <a href="${resetLink}">here</a> to set your password.</p>`,
    });
  }
}
