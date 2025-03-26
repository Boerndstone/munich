import ScrollProgress from "@stimulus-components/scroll-progress";

export default class extends ScrollProgress {
  connect() {
    super.connect();
    console.log("Do what you want here.");
  }
}
