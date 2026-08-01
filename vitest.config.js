import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'jsdom',
		include: [ 'tests/js/**/*.test.js' ],
		coverage: {
			provider: 'v8',
			reportsDirectory: 'bin/reports/coverage-js',
			reporter: [ 'text', 'lcov' ],
			include: [ 'writing-status.js' ],
		},
	},
} );
