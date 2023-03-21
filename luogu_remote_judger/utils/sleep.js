export default function sleep(timeout) {
  return new Promise(resolve => {
    setTimeout(() => resolve(true), timeout);
  });
}
