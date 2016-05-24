/**
 * LogOff.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class LogOff  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.Boolean saveSettings;

    private com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings;

    private java.lang.String[] readMessageIDs;

    private com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList;

    public LogOff() {
    }

    public LogOff(
           java.lang.String ticket,
           java.lang.Boolean saveSettings,
           com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings,
           java.lang.String[] readMessageIDs,
           com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
           this.ticket = ticket;
           this.saveSettings = saveSettings;
           this.settings = settings;
           this.readMessageIDs = readMessageIDs;
           this.messageList = messageList;
    }


    /**
     * Gets the ticket value for this LogOff.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this LogOff.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the saveSettings value for this LogOff.
     * 
     * @return saveSettings
     */
    public java.lang.Boolean getSaveSettings() {
        return saveSettings;
    }


    /**
     * Sets the saveSettings value for this LogOff.
     * 
     * @param saveSettings
     */
    public void setSaveSettings(java.lang.Boolean saveSettings) {
        this.saveSettings = saveSettings;
    }


    /**
     * Gets the settings value for this LogOff.
     * 
     * @return settings
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Setting[] getSettings() {
        return settings;
    }


    /**
     * Sets the settings value for this LogOff.
     * 
     * @param settings
     */
    public void setSettings(com.woodwing.enterprise.interfaces.services.wfl.Setting[] settings) {
        this.settings = settings;
    }


    /**
     * Gets the readMessageIDs value for this LogOff.
     * 
     * @return readMessageIDs
     */
    public java.lang.String[] getReadMessageIDs() {
        return readMessageIDs;
    }


    /**
     * Sets the readMessageIDs value for this LogOff.
     * 
     * @param readMessageIDs
     */
    public void setReadMessageIDs(java.lang.String[] readMessageIDs) {
        this.readMessageIDs = readMessageIDs;
    }


    /**
     * Gets the messageList value for this LogOff.
     * 
     * @return messageList
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MessageList getMessageList() {
        return messageList;
    }


    /**
     * Sets the messageList value for this LogOff.
     * 
     * @param messageList
     */
    public void setMessageList(com.woodwing.enterprise.interfaces.services.wfl.MessageList messageList) {
        this.messageList = messageList;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof LogOff)) return false;
        LogOff other = (LogOff) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.saveSettings==null && other.getSaveSettings()==null) || 
             (this.saveSettings!=null &&
              this.saveSettings.equals(other.getSaveSettings()))) &&
            ((this.settings==null && other.getSettings()==null) || 
             (this.settings!=null &&
              java.util.Arrays.equals(this.settings, other.getSettings()))) &&
            ((this.readMessageIDs==null && other.getReadMessageIDs()==null) || 
             (this.readMessageIDs!=null &&
              java.util.Arrays.equals(this.readMessageIDs, other.getReadMessageIDs()))) &&
            ((this.messageList==null && other.getMessageList()==null) || 
             (this.messageList!=null &&
              this.messageList.equals(other.getMessageList())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getSaveSettings() != null) {
            _hashCode += getSaveSettings().hashCode();
        }
        if (getSettings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSettings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSettings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getReadMessageIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getReadMessageIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getReadMessageIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMessageList() != null) {
            _hashCode += getMessageList().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(LogOff.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">LogOff"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("saveSettings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SaveSettings"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("settings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Settings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Setting"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Setting"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("readMessageIDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ReadMessageIDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("messageList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MessageList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MessageList"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
