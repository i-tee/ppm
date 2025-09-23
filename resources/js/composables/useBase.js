// resources/js/composables/useBase.js

export function useBase() {

  const formatPrice = (value) => {

    value *= 1;

    return value.toLocaleString('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 2
    });

  };

  // Здесь можешь добавлять другие общие методы, например:
  // const capitalize = (str) => { ... };
  
  const formatDate = d => new Date(d).toLocaleDateString('ru-RU')

  return {
    formatPrice,
    formatDate
  };

}