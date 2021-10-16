<template>
    <section>
        <div class="alert alert-success" v-show="showSuccessMessage" role="alert"> The product has been created successfully! </div>
         <div class="alert alert-danger" v-show="showSystemErrorMessage" role="alert"> Application error contact with your system administrator ! </div>
         <div class="alert alert-danger" v-show="showErrorMessage" role="alert">
             <h4 class="alert-heading">Validation failed !</h4>
             <ul v-for="(error,index) in errorMessages">
                 <li>{{ error }}</li>
             </ul>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone ref="myVueDropzone" id="dropzone" :options="dropzoneOptions" @vdropzone-success="imageUploadSuccess"></vue-dropzone>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="variant in variants"
                                                :value="variant.id">
                                            {{ variant.title }} {{ item.selected_id }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price</td>
                                    <td>Stock</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="variant_price in product_variant_prices">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button @click="updateProduct" type="submit" class="btn btn-lg btn-primary">Update</button>
        <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        product: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            showSuccessMessage : false,
            showErrorMessage : false,
            errorMessages: [],
            product_name: this.product.title,
            product_sku: this.product.sku,
            description: this.product.description,
            images: [],
            product_variant: [],
            product_variant_prices: [],
            dropzoneOptions: {
                url: '/image-store',
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }
        }
    },
    methods: {
        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },
        //image upload response
        imageUploadSuccess(file, response) {
            this.images.push(response);
        },
        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach((item,key) => {
                this.product_variant_prices.push({
                    title: item,
                    price: this.product.variants[key].price,
                    stock: this.product.variants[key].stock,
                    id :  this.product.variants[key].id
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // update product into database
        updateProduct() {
            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }
            this.showErrorMessage = false;
            this.errorMessages = [];
            axios.put('/product/' + this.product.id, product).then( response => {
                if (!response.data.success) {
                    if (response.data.errors != undefined){
                        for (const [key, value] of Object.entries(response.data.errors)) {
                        this.errorMessages.push(value[0]);
                        }
                        this.showErrorMessage = true;
                        setTimeout(() => this.showErrorMessage = false, 5000)
                    } else {
                        this.showSystemErrorMessage = true;
                        setTimeout(() => this.showSystemErrorMessage = false, 5000)
                    }
                } else {
                    this.showSuccessMessage = true;
                    setTimeout(() => this.showSuccessMessage = false, 5000)
                }
            }).catch(error => {
               this.showSystemErrorMessage = true;
               setTimeout(() => this.showSystemErrorMessage = false, 5000)
            })
        },
        loadProduct() {
            // for 1st varinat
            let tagOne = [];
            let tagTwo = [];
            let tagThree = [];

            this.product.variants.map((varint) => {
                console.log(varint);
                if(!tagOne.includes(varint.one.variant) && varint.one.variant != undefined  ){
                    tagOne.push(varint.one.variant);
                }
                if(!tagTwo.includes(varint.two.variant) && varint.two.variant != undefined ){
                    tagTwo.push(varint.two.variant);
                }
                if(!tagThree.includes(varint.three.variant) && varint.three.variant != undefined ){
                    tagThree.push(varint.three.variant);
                }
            })

            if(tagOne.length > 0 ) {
                this.product_variant.push({
                    option: this.product.variants[0].one.variant_id,
                    tags: tagOne
                })
            }

            if(tagTwo.length > 0 ) {
                this.product_variant.push({
                    option: this.product.variants[0].two.variant_id,
                    tags: tagTwo
                })
            }

            if(tagThree.length > 0 ) {
                this.product_variant.push({
                    option: this.product.variants[0].three.variant_id,
                    tags: tagThree
                })
            }

            this.product.images.map((image) => {
                console.log(image)
                var file = { size: 102400, name: "Product Images", type: "image/png" };
                var url = '/storage/'+image.file_path;
                this.images.push(image.file_path);
                this.$refs.myVueDropzone.manuallyAddFile(file, url);
            })

            this.checkVariant();
        }
    },
    mounted() {
        this.loadProduct();
        console.log('Component mounted.')
    }
}
</script>
