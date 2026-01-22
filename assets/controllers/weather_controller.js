import { Controller } from "stimulus";

const apiKey = "a7697c3554bba2e46e68d11f7ad2f5f9"; // Replace with your actual API key

export default class extends Controller {
  async connect() {
    const areaLng = this.element.dataset.weatherAreaLng;
    const areaLat = this.element.dataset.weatherAreaLat;
    const currentWeatherUrl = `https://api.openweathermap.org/data/2.5/weather?lat=${areaLat}&lon=${areaLng}&lang=de&date={date}&appid=${apiKey}&units=metric`;
    const forecastWeatherUrl = `https://api.openweathermap.org/data/2.5/forecast?lat=${areaLat}&lon=${areaLng}&lang=de&appid=${apiKey}&units=metric`;

    try {
      // Fetch current weather data
      const currentResponse = await fetch(currentWeatherUrl);
      if (!currentResponse.ok) {
        throw new Error(`HTTP error! status: ${currentResponse.status}`);
      }
      const currentWeatherData = await currentResponse.json();

      const currentDate = new Date(currentWeatherData.dt * 1000);
      const formattedDate = currentDate.toLocaleDateString("de-DE", {
        weekday: "long",
        year: "numeric",
        month: "short",
        day: "numeric",
      });

      function convertDateStringToDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString("de-DE", {
          year: "numeric",
          month: "short",
          day: "numeric",
        });
      }

      const celsius = Math.round(currentWeatherData.main.temp);
      document.querySelector(
        "#temperature"
      ).textContent = `${formattedDate} Temperatur: ${celsius}°C`;

      const currentIconCode = currentWeatherData.weather[0].icon;
      const currentIconUrl = `https://openweathermap.org/img/wn/${currentIconCode}@2x.png`;
      document.querySelector(
        ".current-weather .icon"
      ).innerHTML = `<img src="${currentIconUrl}" alt="weather icon">`;
      document.querySelector(".forecast-text").innerHTML = `hallo`;

      // Fetch forecast weather data
      const forecastResponse = await fetch(forecastWeatherUrl);
      if (!forecastResponse.ok) {
        throw new Error(`HTTP error! status: ${forecastResponse.status}`);
      }
      const forecastData = await forecastResponse.json();

      const forecastDays = document.querySelectorAll(".day");
      for (let i = 0; i < forecastDays.length; i++) {
        const forecastIndex = (i + 1) * 8; // Get data for every 24 hours (3-hour intervals * 8 = 1 day)
        const forecastDay = forecastData.list[forecastIndex];

        // Update forecast icons and temperature
        const forecastIconCode = forecastDay.weather[0].icon;
        const forecastIconUrl = `https://openweathermap.org/img/wn/${forecastIconCode}@2x.png`;
        const formattedForecastDate = convertDateStringToDate(
          forecastDay.dt_txt
        );
        forecastDays[i].querySelector(
          ".forecast-text"
        ).innerHTML = `${formattedForecastDate}`;
        forecastDays[i].querySelector(
          ".icon"
        ).innerHTML = `<img src="${forecastIconUrl}" alt="forecast icon">`;
        forecastDays[i].querySelector(".temp").textContent = `${Math.round(
          forecastDay.main.temp
        )}°C`;
      }
      // You can now use currentWeatherData and forecastData to update your UI
    } catch (error) {
    }
  }
}
