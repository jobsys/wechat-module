<template>
	<div class="page-container">
		<ExResult title="绑定失败" type="error" :description="message">
			<template #extra>
				<Button v-if="redirect" size="small" type="primary" @click="onRedirect">手动登录 </Button>
			</template>
		</ExResult>
	</div>
</template>
<script setup>
import { ExResult } from "@mobile/components"
import { Button } from "vant"
import { router } from "@inertiajs/vue3"

const props = defineProps({
	message: {
		type: String,
		default: "企业微信绑定数据异常，请联系管理员",
	},
	redirect: {
		type: String,
		default: "",
	},
})

const onRedirect = () => {
	router.visit(props.redirect)
}
</script>
