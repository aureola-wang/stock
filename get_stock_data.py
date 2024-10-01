import tushare as ts
import json
import os

# 初始化 Tushare API（请使用你自己的 API token）
pro = ts.pro_api('your_tushare_api_token')  # 请替换为你的 API token

# 获取 A 股历史数据（例如，贵州茅台 sh600519）
df = pro.daily(ts_code='600519.SH', start_date='20230101', end_date='20231001')

# 将数据转换为字典格式
data = df.to_dict(orient='records')

# 确保存在 data 目录，如果不存在则创建
if not os.path.exists('data'):
    os.makedirs('data')

# 将数据保存为 JSON 文件
with open('stock_data.json', 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=4)

print("股票数据已成功获取并保存到 stock_data.json")
