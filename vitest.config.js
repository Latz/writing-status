import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'jsdom',
		include: [ 'tests/js/**/*.test.js' ],
		// Disables the default per-file jsdom environment teardown/setup,
		// cutting wall time roughly 2.5-3x (~4.7s -> ~1.9s locally). Safe here
		// because every test file already fully resets the DOM it touches
		// (document.body.innerHTML rebuilt per fixture, window.wp deleted in
		// afterEach where set) rather than relying on isolate:true to do it —
		// verified stable across repeated runs with --sequence.shuffle.
		pool: 'threads',
		poolOptions: {
			threads: {
				isolate: false,
			},
		},
		coverage: {
			provider: 'v8',
			reportsDirectory: 'bin/reports/coverage-js',
			reporter: [ 'text', 'lcov' ],
			include: [ 'writing-status.js' ],
		},
	},
} );
