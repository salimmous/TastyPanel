export class ApiError extends Error {
  constructor(url, status, data) {
    super(`API ${url} failed: ${status}`);
    if (Error.captureStackTrace) {
      Error.captureStackTrace(this, ApiError);
    }
    this.name = 'ApiError';
    this.url = url;
    this.status = status;
    this.data = data;
  }
}
