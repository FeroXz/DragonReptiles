export default {
  preset: 'ts-jest/presets/default-esm',
  testEnvironment: 'jsdom',
  extensionsToTreatAsEsm: ['.ts', '.tsx'],
  roots: ['<rootDir>/src', '<rootDir>/tests'],
  moduleFileExtensions: ['ts', 'tsx', 'js', 'json'],
  moduleNameMapper: {
    '^@lib/(.*)\\.js$': '<rootDir>/src/lib/$1',
    '^@components/(.*)\\.js$': '<rootDir>/src/components/$1',
    '^@data/(.*)\\.js$': '<rootDir>/src/data/$1',
    '^@i18n/(.*)\\.js$': '<rootDir>/src/i18n/$1',
    '^@lib/(.*)$': '<rootDir>/src/lib/$1',
    '^@components/(.*)$': '<rootDir>/src/components/$1',
    '^@data/(.*)$': '<rootDir>/src/data/$1',
    '^@i18n/(.*)$': '<rootDir>/src/i18n/$1',
    '^(\\.{1,2}/.*)\\.js$': '$1'
  },
  setupFilesAfterEnv: ['<rootDir>/tests/setupTests.ts'],
  globals: {
    'ts-jest': {
      useESM: true
    }
  }
};
