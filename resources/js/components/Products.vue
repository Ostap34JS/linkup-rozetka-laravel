<template>
    <div>
        <pagination class="table-responsive mb-2" :data="products" @pagination-change-page="fetch"></pagination>
        <table class="table table-responsive-lg table-striped table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="product in products.data">
                <th>{{ product.id }}</th>
                <th>{{ product.title }}</th>
                <th>{{ product.price }} ГРН</th>
                <th>
                    <div class="btn-group">
                        <a :href="route('products.show', {id: product.id})" class="btn btn-info">View</a>
                        <a :href="route('products.edit', {id: product.id})" class="btn btn-secondary">Edit</a>
                        <button class="btn btn-danger" @click="destroy(product.id)">Delete</button>
                    </div>
                </th>
            </tr>
            </tbody>
        </table>
        <pagination class="table-responsive mb-2" :data="products" @pagination-change-page="fetch"></pagination>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                products: {},
            }
        },
        mounted() {
            this.fetch();
        },
        props: ['category_id'],
        methods: {
            fetch(page = 1) {
                const url = this.getFetchUrl(page);
                axios.get(url)
                    .then(({data}) => {
                        this.products = data;
                    });
            },
            getFetchUrl(page) {
                if (this.$props.category_id !== undefined){
                    return route('category.products', {
                        id: this.$props.category_id,
                        page: page
                    });
                }
                return route('api.products.index', {page: page});
            },
            destroy(id) {
                if(confirm('Are you sure you want to delete this product?')) {
                    axios.delete(route('api.products.destroy', {id: id}))
                        .then(function (response) {
                            //todo
                            console.log(response)
                        });
                }
            }
        }
    }
</script>
