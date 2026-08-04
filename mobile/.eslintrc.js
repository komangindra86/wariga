module.exports = {
    root: true,
    extends: '@react-native',
    plugins: ['import'],
    rules: {
        'import/order': [
            'error',
            {
                'newlines-between': 'always',
                alphabetize: { order: 'asc', caseInsensitive: true },
            },
        ],
    },
};
