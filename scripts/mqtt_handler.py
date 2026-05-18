#!/usr/bin/env python3
"""
MQTT 长连接客户端模块
- 同时支持发布和订阅
- 自动重连
- paho-mqtt v1/v2 兼容
"""

import json
import time
import logging
from datetime import datetime

import config

try:
    import paho.mqtt.client as mqtt
    PAHO_V2 = hasattr(mqtt, 'CallbackAPIVersion')
except ImportError:
    raise ImportError("请先安装 paho-mqtt: pip3 install paho-mqtt")

logger = logging.getLogger(__name__)


class MqttHandler:
    """MQTT 长连接处理器"""
    
    def __init__(self, device_code, on_command=None, on_config=None):
        """
        初始化 MQTT 处理器
        
        Args:
            device_code: 设备编码
            on_command: 收到手动控制命令的回调函数(actions_dict)
            on_config: 收到自动配置命令的回调函数(config_type, config_data, config_id)
        """
        self.device_code = device_code
        self.on_command = on_command
        self.on_config = on_config
        self.client = None
        self.connected = False
        self._reconnect_count = 0
        
        # 创建客户端
        self._create_client()
    
    def _create_client(self):
        """创建 MQTT 客户端（兼容 v1/v2）"""
        client_id = f"device_{self.device_code}_{int(time.time())}"
        
        if PAHO_V2:
            self.client = mqtt.Client(
                callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
                client_id=client_id,
                clean_session=False
            )
        else:
            self.client = mqtt.Client(client_id=client_id, clean_session=False)
        
        # 设置认证
        self.client.username_pw_set(config.MQTT_USERNAME, config.MQTT_PASSWORD)
        
        # 设置回调
        self.client.on_connect = self._on_connect
        self.client.on_disconnect = self._on_disconnect
        self.client.on_message = self._on_message
        
        # 启用自动重连
        self.client.reconnect_delay_set(
            min_delay=config.RECONNECT_MIN_DELAY,
            max_delay=config.RECONNECT_MAX_DELAY
        )
    
    def _on_connect(self, client, userdata, flags, rc_or_reason, properties=None):
        """连接成功回调"""
        if PAHO_V2:
            if rc_or_reason.is_failure:
                logger.error(f"MQTT 连接失败: {rc_or_reason}")
                return
        else:
            if rc_or_reason != 0:
                logger.error(f"MQTT 连接失败，返回码: {rc_or_reason}")
                return
        
        self.connected = True
        self._reconnect_count = 0
        logger.info("✓ MQTT 连接成功")
        
        # 订阅命令和配置 Topic
        self._subscribe_topics()
        
        # 发送设备上线状态
        self.publish_status(True)
    
    def _on_disconnect(self, client, userdata, rc_or_flags, rc=None, properties=None):
        """断开连接回调"""
        self.connected = False
        
        if PAHO_V2:
            if rc is not None and rc != 0:
                logger.warning(f"⚠ MQTT 异常断开，返回码: {rc}")
            else:
                logger.info("✓ MQTT 正常断开")
        else:
            if rc_or_flags != 0:
                logger.warning(f"⚠ MQTT 异常断开，返回码: {rc_or_flags}")
            else:
                logger.info("✓ MQTT 正常断开")
    
    def _subscribe_topics(self):
        """订阅所有需要的 Topic"""
        topics = [
            f"chambers/{self.device_code}/command/manual",
            f"chambers/{self.device_code}/config/auto",
        ]
        
        for topic in topics:
            self.client.subscribe(topic, qos=config.MQTT_QOS)
            logger.info(f"✓ 已订阅: {topic}")
    
    def _on_message(self, client, userdata, msg):
        """收到消息回调"""
        topic = msg.topic
        
        try:
            payload = json.loads(msg.payload.decode())
        except json.JSONDecodeError as e:
            logger.error(f"✗ 收到非法 JSON: {e}")
            return
        except Exception as e:
            logger.error(f"✗ 解析消息失败: {e}")
            return
        
        logger.info(f"【收到消息】Topic: {topic}")
        
        try:
            if 'command' in topic:
                self._handle_command(payload)
            elif 'config' in topic:
                self._handle_config(payload)
        except Exception as e:
            logger.error(f"✗ 处理消息时发生错误: {e}")
    
    def _handle_command(self, payload):
        """处理手动控制命令"""
        command_id = payload.get('command_id')
        actions = payload.get('actions', {})
        
        logger.info(f"【命令】ID: {command_id}, Actions: {actions}")
        
        # 调用外部回调
        executed_actions = {}
        if self.on_command:
            executed_actions = self.on_command(actions)
        
        # 发送 ACK
        self.publish_ack(command_id, 'success', executed_actions)
    
    def _handle_config(self, payload):
        """处理自动配置命令"""
        config_id = payload.get('config_id')
        config_type = payload.get('control_type')
        config_data = payload.get('config', {})
        
        logger.info(f"【配置】ID: {config_id}, Type: {config_type}")
        
        # 调用外部回调
        success = False
        if self.on_config:
            success = self.on_config(config_type, config_data, config_id)
        
        # 发送 ACK
        self.publish_ack(config_id, 'success' if success else 'failed')
    
    def connect(self):
        """连接 MQTT Broker"""
        try:
            logger.info(f"正在连接 MQTT Broker ({config.MQTT_BROKER}:{config.MQTT_PORT})...")
            self.client.connect(
                config.MQTT_BROKER,
                config.MQTT_PORT,
                keepalive=config.MQTT_KEEPALIVE
            )
            self.client.loop_start()
            return True
        except Exception as e:
            logger.error(f"✗ MQTT 连接错误: {e}")
            return False
    
    def disconnect(self):
        """断开连接"""
        if self.client:
            self.client.loop_stop()
            self.client.disconnect()
            logger.info("✓ MQTT 已断开")
    
    def publish_data(self, data):
        """
        发布传感器数据
        
        Args:
            data: 传感器数据字典
        """
        if not self.connected:
            logger.warning("⚠ MQTT 未连接，无法上报数据")
            return False
        
        try:
            topic = f"chambers/{self.device_code}/data"
            payload = json.dumps(data)
            
            result = self.client.publish(topic, payload, qos=config.MQTT_QOS)
            
            if PAHO_V2:
                success = not result.rc.is_failure if hasattr(result.rc, 'is_failure') else result.rc == mqtt.MQTT_ERR_SUCCESS
            else:
                success = result.rc == mqtt.MQTT_ERR_SUCCESS
            
            if success:
                logger.info(f"✓ 数据已上报: {topic}")
            else:
                logger.error(f"✗ 数据上报失败: {topic}")
            
            return success
            
        except Exception as e:
            logger.error(f"✗ 数据上报异常: {e}")
            return False
    
    def publish_status(self, online=True):
        """
        发布设备在线状态
        
        Args:
            online: 是否在线
        """
        if not self.connected:
            return False
        
        try:
            topic = f"chambers/{self.device_code}/status"
            payload = json.dumps({
                'online': online,
                'timestamp': datetime.now().isoformat(),
                'device_code': self.device_code,
            })
            
            self.client.publish(topic, payload, qos=config.MQTT_QOS)
            logger.info(f"✓ 状态已上报: {'在线' if online else '离线'}")
            return True
            
        except Exception as e:
            logger.error(f"✗ 状态上报异常: {e}")
            return False
    
    def publish_ack(self, command_id, status, executed_actions=None):
        """
        发布 ACK 确认
        
        Args:
            command_id: 命令ID
            status: 'success' 或 'failed'
            executed_actions: 实际执行的动作（可选）
        """
        if not self.connected:
            return False
        
        try:
            topic = f"chambers/{self.device_code}/ack"
            ack = {
                'command_id': command_id,
                'status': status,
                'received_at': int(time.time()),
            }
            
            if executed_actions:
                ack['executed_actions'] = executed_actions
            
            self.client.publish(topic, json.dumps(ack), qos=config.MQTT_QOS)
            logger.info(f"✓ ACK 已发送: {command_id} -> {status}")
            return True
            
        except Exception as e:
            logger.error(f"✗ ACK 发送异常: {e}")
            return False
    
    def is_connected(self):
        """检查连接状态"""
        return self.connected and self.client.is_connected()
