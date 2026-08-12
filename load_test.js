import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '10s', target: 20 },  // Ramp up to 20 users
    { duration: '30s', target: 100 }, // Spike load to 100 virtual users
    { duration: '10s', target: 0 },   // Cool down
  ],
  insecureSkipTLSVerify: true,        // Ignore local self-signed cert warnings
};

export default function () {
  const res = http.get('https://localhost/');
  check(res, {
    'status is 200': (r) => r.status === 200,
  });
  sleep(0.1);
}
