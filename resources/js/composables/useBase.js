// resources/js/composables/useBase.js

export function useBase() {
  const formatPrice = (value) => {
    return value.toLocaleString('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 2
    });
  };

  // Здесь можешь добавлять другие общие методы, например:
  // const formatDate = (date) => { ... };
  // const capitalize = (str) => { ... };

  return {
    formatPrice
  };
}