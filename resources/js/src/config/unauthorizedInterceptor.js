export default function created () {
    this.$http.interceptors.response.use(undefined, function (err) {
        return new Promise(function (resolve, reject) {
            if (err.status === 401 && err.config && !err.config.__isRetryRequest) {
                this.$store.dispatch(logout)
            }
            throw err;
        });
    });
}
