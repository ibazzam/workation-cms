import axios from 'axios';
import transportHoldTest from './transport-hold.mjs';

const baseUrl = process.env.BASE_URL;
if (!baseUrl) {
  console.log('BASE_URL not set — skipping E2E tests. To run, set BASE_URL to your staging URL.');
  process.exit(0);
}

(async () => {
  try {
    console.log(`Running E2E against ${baseUrl}`);
    await transportHoldTest(axios.create({ baseURL: baseUrl, timeout: 5000 }));
    console.log('E2E tests passed');
    process.exit(0);
  } catch (err) {
    console.error('E2E tests failed:', err.message || err);
    process.exit(2);
  }
})();
