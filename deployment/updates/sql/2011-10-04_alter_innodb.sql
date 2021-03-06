alter table file_sync modify file_path varchar(512), add custom_data text,engine innodb;
alter table entry drop index search_text_index, drop index search_text_discrete_index, engine innodb;
alter table kuser drop index search_text_index, engine innodb;
alter table kshow drop index search_text_index, engine innodb;
alter table short_link engine innodb;
alter table batch_job engine innodb;
alter table flavor_asset engine innodb;
alter table kuser_to_user_role engine innodb;
alter table kvote engine innodb;
alter table kwidget_log engine innodb;
alter table mail_job engine innodb;
alter table media_info engine innodb;
alter table metadata engine innodb;
alter table metadata_profile engine innodb;
alter table metadata_profile_field engine innodb;
alter table moderation engine innodb;
alter table moderation_flag engine innodb;
alter table notification engine innodb;
alter table partner engine innodb;
alter table partner_activity engine innodb;
alter table partner_stats engine innodb;
alter table partner_transactions engine innodb;
alter table partnership engine innodb;
alter table permission engine innodb;
alter table permission_item engine innodb;
alter table permission_to_permission_item engine innodb;
alter table pr_news engine innodb;
alter table priority_group engine innodb;
alter table puser_kuser engine innodb;
alter table puser_role engine innodb;
alter table roughcut_entry engine innodb;
alter table scheduler engine innodb;
alter table scheduler_config engine innodb;
alter table scheduler_status engine innodb;
alter table scheduler_worker engine innodb;
alter table storage_profile engine innodb;
alter table syndication_feed engine innodb;
alter table tagword_count engine innodb;
alter table temp_entry_update engine innodb;
alter table temp_updated_kusers_storage_usage engine innodb;
alter table tmp engine innodb;
alter table track_entry engine innodb;
alter table ui_conf engine innodb;
alter table upload_token engine innodb;
alter table user_login_data engine innodb;
alter table user_role engine innodb;
alter table virus_scan_profile engine innodb;
alter table widget engine innodb;
alter table widget_log engine innodb;
alter table work_group engine innodb;

drop table if exists system_user;



